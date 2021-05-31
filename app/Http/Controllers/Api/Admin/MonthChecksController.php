<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\CheckOrder;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\SimpleResponse;
use App\Models\Worker;
use App\TemplateMessageSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthChecksController extends SimpleController
{
    protected function getModel()
    {
        return new MonthCheck();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input(), $request->user());
    }

    public function query(array $data, $user): Builder {

        $model = $this->getModel();
        $model = $model->with(["workers", "workers.worker", "checkOrder", "checkOrder.region"]);

        // 员工名称搜索
        if ($worker_name = data_get($data, "worker_name")) {
            $model->whereHas("workers.worker", 
                function (Builder $query) use($worker_name) {
                    $query->where('name', 'like', "%{$worker_name}%");
                }
            );
        }
        // 企业名称搜索
        if ($enterprise_name = data_get($data, "enterprise_name")) {
            $model->whereHas("checkOrder",
                function (Builder $query) use($enterprise_name) {
                    $query->where('enterprise_name', 'like', "%{$enterprise_name}%");
                }
            );
        }
        // 区域搜索
        // 如果登录为区域经理，则只展示此区域数据
        if (data_get($user, "type") == 2) {
            $model->whereHas("checkOrder",
                function (Builder $query) use($user) {
                    $query->where('region_id', '=', data_get($user, "region_id"));
                }
            );
            // $model->where("region_id", "=", data_get($user, "region_id"));
        } else {
            if ($region_id = data_get($data, "region_id")) {
                $model->whereHas("checkOrder",
                    function (Builder $query) use($region_id) {
                        $query->where('region_id', '=', $region_id);
                    }
                );
            }
        }
        // 状态搜索
        if (-2 < ($status = data_get($data, "status"))){
            $model->whereHas("workers", 
                function ($query) use ($status){
                    $query->where("status", "=", $status);
                }
            );
        }
        // 派单时间范围搜索
        if ($date_range = data_get($data, "date_range")) {
            $model->whereHas("workers", 
                function ($query) use ($date_range){
                    $query->whereBetween("created_at", explode("~", $date_range));
                }
            );
        }

        // 月检合同订单ID
        if ($check_order_id = data_get($data, "check_order_id")) {
            $model->where("check_order_id", "=", $check_order_id);
        }

        return $model;
    }

    public function index()
    {
        $request = request();
        $model = $this->search($request);

        $check_order_id = $request->input("check_order_id");

        $total_service_time = MonthCheckWorker::with([])
            ->where("check_order_id", "=", $check_order_id)
            ->sum("service_time");
        $total_earnings = MonthCheckWorker::with([])
            ->where("check_order_id", "=", $check_order_id)
            ->sum("earnings");
        $total_client_settlement = MonthCheckWorker::with([])
        ->where("check_order_id", "=", $check_order_id)
        ->sum("client_settlement");
        $total_profit = MonthCheckWorker::with([])
        ->where("check_order_id", "=", $check_order_id)
        ->sum("profit");

        foreach ($this->orderBy as $val) {
            $column = data_get($val, 'column');
            $direction = data_get($val, 'direction');
            if ($column && $direction) {
                $model->orderBy($column, $direction);
            }
        }

        if ($request->header('simple-page') == 'true') {
            $list = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $list = $model->paginate($request->input("per-page", 15));
        }

        $data = [
            "total_service_time" => $total_service_time,
            "total_earnings" => $total_earnings,
            "total_client_settlement" => $total_client_settlement,
            "total_profit" => $total_profit,
            "list" => $list
        ];

        return $data;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $data = $this->validate($request, [
            "check_order_id" => ["required", "integer"],
            "worker_num" => ["required", "integer"],
            "time_length" => ["required", "integer"],
            "door_time" => ["required", "date_format:Y-m-d H:i:s"],
            "workers"=>["array","nullable"],
            "workers.*"=>["integer"],
            "remark" => ["nullable","string"]
        ]);

        $data["type"] = 2;
        $data["left_worker_num"] = data_get($data, "worker_num") - count(data_get($data, "workers"));

        $checkOrderInfo = CheckOrder::with([])->find(data_get($data, "check_order_id"));
        if (!$checkOrderInfo) throw new NoticeException("网络错误");
        if (data_get($checkOrderInfo, "remaining_service_num") == 0) throw new NoticeException("剩余服务次数没有啦，不能派单了");

        return DB::transaction(
            function () use($data, $checkOrderInfo) {
                $create = $this->getModel()->with([])->create($data);
                if($create){
                    $checkOrderInfo->door_time = data_get($data, "door_time");
                    $checkOrderInfo->save();

                    $workers = collect(data_get($data,"workers"));
                    $workers->each(function ($item) use($create, $checkOrderInfo){
                        // 判断是否有月检检查员工信息 如果没有则新建
                        $staff = MonthCheckWorker::with([])
                            ->where("month_check_id", "=", data_get($create, "id", $create))
                            ->where("worker_id", "=", $item)->first();
                        if(!$staff){
                            $staff_info['check_order_id'] = data_get($create, "check_order_id");
                            $staff_info['month_check_id'] = data_get($create, "id", $create);
                            $staff_info["worker_id"] = $item;
                            $staff_info["type"] = 1;
                            $monthCheckWorker = MonthCheckWorker::with([])->create($staff_info);
                            // log_action($monthCheckWorker, "月检合同订单-派单员工 添加：订单编号 ".data_get($checkOrderInfo, "order_code"), "月检合同订单-订单派单员工");
                            // 添加派单记录
                            $monthCheckWorkerActionsInfo["check_order_id"] = data_get($create, "check_order_id");
                            $monthCheckWorkerActionsInfo["month_check_worker_id"] = $monthCheckWorker->id;
                            $monthCheckWorkerActionsInfo["month_check_id"] = data_get($create, "id", $create);
                            $monthCheckWorkerActionsInfo["worker_id"] = $item;
                            $monthCheckWorkerActionsInfo["type"] = 1;
                            $monthCheckWorkerActionsInfo["action_time"] = date("Y-m-d H:i:s");
                            $action = MonthCheckWorkerAction::with([])->create($monthCheckWorkerActionsInfo);
                            
                            // 推送派单模板消息
                            $worker = Worker::with([])->find($item);
                            TemplateMessageSend::sendOrderToWorker($worker,$action);

                            // log_action($action, "月检合同订单-员工工作记录-后台派单 添加：订单编号 " . data_get($checkOrderInfo, "order_code"), "月检合同订单-员工工作记录");
                        }
                    });

                    $checkOrderInfo->remaining_service_num -= 1;
                    $checkOrderInfo->save();

                    

                    log_action($create, "月检合同订单-月检记录 添加：订单编号 " . data_get($checkOrderInfo, "order_code"), "月检合同订单-月检记录");
                    return SimpleResponse::success("添加成功");
                }
                return SimpleResponse::error("添加失败");
            }
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        return $this->getModel()->with([
            "checkOrder",
            "workers",
            "workers.worker",
            "siteConditions",
            "siteConditions.files",
            "checkOrderComments",
            "checkOrderComments.files"
        ])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $data = $this->validate($request, [
            "worker_num" => ["required", "integer"],
            "time_length" => ["required", "integer"],
            "door_time" => ["required", "date_format:Y-m-d H:i:s"],
            "workers"=>["array","nullable"],
            "workers.*"=>["integer"],
            "remark" => []
        ]);

        $data["left_worker_num"] = data_get($data, "worker_num") - count(data_get($data, "workers"));

        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        } else {
            throw new NoticeException("数据异常");
        }

        if(data_get($data,"worker_num") < count(data_get($data,"workers"))){
            throw new NoticeException("用工人数不能低于分配员工总数");
        }

        $workers = collect(data_get($data,"workers"));
        $workers->map(function ($w_id){
            $worker = Worker::with([])->find($w_id);
            if($worker->work_status==0){
                throw new NoticeException("员工：".$worker->name."处于休息中，不可派单");
            }
        });

        $monthCheckWorkers = MonthCheckWorker::with([])->where("month_check_id", "=", $id)->get();
        // 获得数据库中已经存在的工人
        $presence = $monthCheckWorkers->filter(
            function ($val, $key) use($workers) {
                foreach ($workers as $k => $v) {
                    if (data_get($val, "worker_id") == $v) {
                        return data_get($val, 'id');
                    }
                }
            }
        );
        $noPresence = MonthCheckWorker::with(["worker"])
            ->where("month_check_id", "=", $id)
            ->whereNotIn('id', $presence)
            ->get();
        // 判断工人是否接单
        $noPresenceIds = [];
        foreach ($noPresence as $val) {
            if (1 < data_get($val, "status")) {
                throw new NoticeException("员工：".data_get($val->worker(), "name")." 已签到，不能删除");
            }
            $noPresenceIds[] = data_get($val, 'id');
        }
        // 删除不存在的工人
        MonthCheckWorker::with([])->whereIn('id', $noPresenceIds)->delete();

        $checkOrderInfo = CheckOrder::with([])->find(data_get($find, "check_order_id"));

        return DB::transaction(
            function () use($find, $data, $checkOrderInfo, $old) {
                if($find->update($data)){
                    $checkOrderInfo->door_time = data_get($data, "door_time");
                    $checkOrderInfo->save();
                    
                    $workers = collect(data_get($data,"workers"));
                    $workers->each(function ($item) use($find, $checkOrderInfo){
                        // 判断是否有月检检查员工信息 如果没有则新建
                        $staff = MonthCheckWorker::with([])
                            ->where("month_check_id", "=", $find->id)
                            ->where("worker_id", "=", $item)->first();
                        if(!$staff){
                            $staff_info['check_order_id'] = $find->check_order_id;
                            $staff_info['month_check_id'] = $find->id;
                            $staff_info["worker_id"] = $item;
                            $staff_info["type"] = 1;
                            $monthCheckWorker = MonthCheckWorker::with([])->create($staff_info);
                            // log_action($monthCheckWorker, "月检合同订单-派单员工 添加：订单编号 ".data_get($checkOrderInfo, "order_code"), "月检合同订单-订单派单员工");
                            // 添加派单记录
                            $monthCheckWorkerActionsInfo["check_order_id"] = $find->check_order_id;
                            $monthCheckWorkerActionsInfo["month_check_worker_id"] = $monthCheckWorker->id;
                            $monthCheckWorkerActionsInfo["month_check_id"] = $find->id;
                            $monthCheckWorkerActionsInfo["worker_id"] = $item;
                            $monthCheckWorkerActionsInfo["type"] = 1;
                            $monthCheckWorkerActionsInfo["action_time"] = date("Y-m-d H:i:s");
                            $action = MonthCheckWorkerAction::with([])->create($monthCheckWorkerActionsInfo);

                            // 推送派单模板消息
                            $worker = Worker::with([])->find($item);
                            TemplateMessageSend::sendOrderToWorker($worker,$action);

                            // log_action($action, "月检合同订单-员工工作记录-后台派单 添加：订单编号 " . data_get($checkOrderInfo, "order_code"), "月检合同订单-员工工作记录");
                        }
                    });
                    log_action($find, "月检合同订单-月检记录 编辑：订单编号 " . data_get($checkOrderInfo, "order_code"), "月检合同订单-月检记录", $old);
                    return SimpleResponse::success("编辑成功");
                }
                return SimpleResponse::error("编辑失败");
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        return false;
    }
}

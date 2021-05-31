<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use App\Models\CheckOrder;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\Worker;
use App\Rules\MobileRule;
use App\TemplateMessageSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FreeCheckOrderController extends SimpleController
{
    protected function getModel()
    {
        return new CheckOrder();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input(), $request->user());
    }

    public function query(array $data, $user): Builder {

        $model = $this->getModel();
        $model = $model->with(["region", "nature", "monthCheckOrder"]);
        $model->where("type", "=", 1);
        // 企业名称搜索
        if ($enterprise_name = data_get($data, "enterprise_name")) $model->where("enterprise_name", "like", "%{$enterprise_name}%");
        // 订单编号 order_code
        if ($order_code = data_get($data, "order_code")) $model->where("order_code", "like", "%{$order_code}%");
        // 联系人搜索
        if ($name = data_get($data, "name")) $model->where("name", "like", "%{$name}%");
        // 联系方式搜索
        if ($mobile = data_get($data, "mobile")) $model->where("mobile", "like", "%{$mobile}%");
        // 如果登录为区域经理，则只展示此区域数据
        if (data_get($user, "type") == 2) {
            $model->where("region_id", "=", data_get($user, "region_id"));
        } else {
            // 所属区域搜索
            if ($region_id = data_get($data, "region_id")) $model->where("region_id", "=", $region_id);
        }
        // 检查状态搜索 0待检查 1已检查 2未检查
        if (-1<($status = data_get($data, "status"))) $model->where("status", "=", $status);
        // 客户状态搜索 0未沟通 1继续沟通 3已完成 -1已作废
        if (-2<($customer_status = data_get($data, "customer_status"))) $model->where("customer_status", "=", $customer_status);
        // 检查时间范围搜索 2020-12-01~2021-01-20 日期范围，使用"~"分隔
        if ($date_range = data_get($data, "date_range")) $model->whereBetween("door_time", explode("~", $date_range));

        return $model;
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
        return false;
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
            "region",
            "nature",
            "monthCheckWorker",
            "monthCheckWorker.worker",
            "siteConditions",
            "siteConditions.files",
            "contracts",
            "contracts.files",
            "checkOrderComments",
            "checkOrderComments.files"
        ])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $data = $this->validate($request, [
            "enterprise_name" => ["required", "string"],
            "building_area" => ["required", "integer"],
            "nature_id" => ["required", "integer"],
            "region_id" => ["required", "integer"],
            "name" => ["required", "string"],
            "mobile" => ["required", new MobileRule()],
            "address" => ["required", "string"],
            "workers"=>["array","nullable"],
            "workers.*"=>["integer"],
            "worker_num" => ["required", "integer"],
            "door_time" => ["required","date_format:Y-m-d H:i:s"],
            "remark" => ["nullable", "string"],
            "long"=>["nullable","numeric"],
            "lat"=>["nullable","numeric"],
        ]);

        $find = $this->getModel()->with([])->find($id);
        if ($find) {
            $old = clone $find;
        } else {
            // return SimpleResponse::error("数据异常");
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

        $monthCheckWorkers = MonthCheckWorker::with([])->where("check_order_id", "=", $id)->get();
        // 获得数据库中已经存在的工人
        $presence = $monthCheckWorkers->filter(
            function ($val, $key) use($workers) {
                foreach ($workers as $k => $v) {
                    if (data_get($val, "worker_id") == $v) {
                        return $val;
                    }
                }
            }
        );
        $noPresence = MonthCheckWorker::with(["worker"])
            ->where("check_order_id", "=", $id)
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

        // 编辑免费检查订单
        return DB::transaction(
            function() use($data, $id, $find, $old) {
                // 更新免费检查订单信息
                $find->update($data);
                // 更新月检记录
                $month_check = MonthCheck::with([])->where('check_order_id', $id)->first();
                $month_check_id = data_get($month_check, "id");
                $month_check_info["worker_num"] = data_get($data, "worker_num");
                $month_check_info["door_time"] = data_get($data, "door_time");
                $month_check_info["remark"] = data_get($data, "remark");
                // 判断月检记录是否存在
                if ($month_check) {
                    $month_check = MonthCheck::with([])->where('id', $month_check_id)->update($month_check_info);
                } else {
                    $month_check_info["check_order_id"] = $id;
                    $month_check = MonthCheck::with([])->create($month_check_info);
                    log_action($month_check,"免费检查订单-月检表记录 添加：订单编号 ".data_get($find,"order_code"), "免费检查订单-月检表记录");
                }
                // 处理已派单员工
                $workers = collect(data_get($data, "workers"));

                $workers->each(
                    function($item) use($month_check, $id, $find, $month_check_id) {

                        //判断是否有免费检查员工信息 如果没有则新建
                        $staff = MonthCheckWorker::with([])
                            ->where("month_check_id", "=", $month_check_id)
                            ->where("worker_id", "=", $item)->first();
                        if (!$staff) {
                            $staff_info['check_order_id'] = $id;
                            $staff_info['month_check_id'] = $month_check_id;
                            $staff_info["worker_id"] = $item;
                            $staff_info["type"] = 1;
                            $monthCheckWorker = MonthCheckWorker::with([])->create($staff_info);
                            log_action($monthCheckWorker,"免费检查订单派单施工员工 添加：订单编号 ".data_get($find,"order_code"), "免费检查订单派单施工员工");

                            // 添加派单记录
                            $monthCheckWorkerActionsInfo["check_order_id"] = $id;
                            $monthCheckWorkerActionsInfo["month_check_worker_id"] = $monthCheckWorker->id;
                            $monthCheckWorkerActionsInfo["month_check_id"] = $month_check_id;
                            $monthCheckWorkerActionsInfo["worker_id"] = $item;
                            $monthCheckWorkerActionsInfo["type"] = 1;
                            $monthCheckWorkerActionsInfo["action_time"] = date("Y-m-d H:i:s");
                            $action = MonthCheckWorkerAction::with([])->create($monthCheckWorkerActionsInfo);

                            // 推送派单模板消息
                            $worker = Worker::with([])->find($item);
                            TemplateMessageSend::sendOrderToWorker($worker,$action);

                            log_action($monthCheckWorker,"免费检查订单-员工工作记录-后台派单 添加：订单编号 ".data_get($find,"order_code"), "免费检查订单-员工工作记录");
                        }
                    }
                );

                log_action($find, "免费检查订单编辑：" . data_get($find, "name"), "免费检查订单", $old);
                return SimpleResponse::success("编辑成功");
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

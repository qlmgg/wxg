<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;
use App\Models\CheckOrder;
use App\Models\Demand;
use App\Models\FixedInspectionRecord;
use App\Models\MonthCheckWorker;
use App\Models\User;
use App\Rules\MobileRule;
use App\TemplateMessageSend;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyInspectionOrderController extends SimpleController
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
        $model = $model->with([
                "region",
                "nature",
                "monthCheck",
                "contractManagement",
                "fixedInspectionRecord",
                "fixedInspectionRecord.fixedCheckItems",
            ]);
        $model->where("type", "=", 2);
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
        // 支付状态搜索 0未支付 1已支付 2部分支付
        if (-1<($pay_status = data_get($data, "pay_status"))) $model->where("pay_status", "=", $pay_status);
        // 赠送状态搜索 0否 1是
        if (-1<($is_gift = data_get($data, "is_gift"))) $model->where("is_gift", "=", $is_gift);
        // 检查时间范围搜索
        if ($date_range = data_get($data, "date_range")) $model->whereBetween("created_at", explode("~", $date_range));

        // 本月是否检修
        if (data_get($data, "is_check") == 1) {
            $model->whereHas(
                "monthCheck",
                function (Builder $query) {
                    $startDateTime = Carbon::today()->firstOfMonth();
                    $endDateTime = Carbon::today()->endOfMonth();
                    $query->where("type", "=", 2);
                    $query->where("status", "=", 0);
                    $query->whereBetween("door_time", [$startDateTime, $endDateTime]);
                }
            );
        }
        if (data_get($data, "is_check") == 2) {
            $model->whereHas(
                "monthCheck",
                function (Builder $query) {
                    $startDateTime = Carbon::today()->firstOfMonth();
                    $endDateTime = Carbon::today()->endOfMonth();
                    $query->where("type", "=", 2);
                    $query->where("status", ">", 0);
                    $query->whereBetween("door_time", [$startDateTime, $endDateTime]);
                }
            );
        }

        return $model;
    }

    /**
     * 列表
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator|View
     */
    public function index()
    {
        $request = request();
        $model = $this->search($request);

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

        foreach ($list as $val) {
            // 获取客户结算总额
            $settlement_amount = MonthCheckWorker::with([])
            ->where('check_order_id', '=', data_get($val, "id"))
            ->where('status', '=', 4)
            ->sum("client_settlement");

            // 计算获得订单剩余金额
            $val->remaining_amount = round($val->money - ((0<$settlement_amount)?$settlement_amount:0), 2);
            $val->save();
        }

        return $list;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request)
    {
        //
        $data = $this->validate($request, [
            "demand_id" => ["required", "integer"],
            "enterprise_name" => ["required", "string"],
            "building_area" => ["required", "integer"],
            "nature_id" => ["required", "integer"],
            "region_id" => ["required", "integer"],
            "name" => ["required", "string"],
            "mobile" => ["required", new MobileRule()],
            "address" => ["required", "string"],
            "fixed_duty" => ["required", "integer"],
            "worker_num" => ["required", "integer"],
            "age_limit" => ["required", "integer"],
            "free_amount" => ["nullable", "numeric"],
            "num_monthly_inspections" => ["required", "integer"],
            "money" => ["required", "numeric", "min:0.01"],
            "payment_type" => ["required", "in:1,2,3"],
            "down_payment" => ["nullable", "numeric"],
            "gift_num" => ["required", "integer"],
            "remark" => ["nullable", "string"],
            "fixed" => ["array","nullable"],
            "fixed.*" => ["integer"],
            "is_show_client_settlement" => ["nullable", "in:0,1"],
        ]);

        if(is_null(data_get($data,"is_show_client_settlement"))){
            unset($data["is_show_client_settlement"]);
        }

        $demandOrder = Demand::with([])->where("id", "=", data_get($data, "demand_id"))->first();
        if (!$demandOrder) return SimpleResponse::error("数据异常");

        $count = $this->getModel()->with([])->where("demand_id", "=", data_get($data, "demand_id"))->where("type", "=", 2)->count();
        if (0 < $count) return SimpleResponse::error("月检订单已存在");

        $data["type"] = 2;
        $data["long"] = data_get($demandOrder, "longitude");
        $data["lat"] = data_get($demandOrder, "latitude");
        $data["user_id"] = data_get($demandOrder, "user_id");
        $data["remaining_service_num"] = ((data_get($data, "num_monthly_inspections") * 12) * data_get($data, "age_limit")) + data_get($data, "gift_num");

        //生成唯一的CODE码
        $code = Carbon::parse(now())->format("YmdHis").data_get($data, "user_id").mt_rand(100,999);
        $data['order_code'] = $code;

        return DB::transaction(
            function () use($data) {
                $create = $this->getModel()->with([])->create($data);
                if($create){
                    // 固定项
                    $fixed = collect(data_get($data, "fixed"));
                    $fixed->each(
                        function($item) use($create) {
                            // $is_exist = FixedInspectionRecord::with([])
                            //     ->where("fixed_check_items_id", "=", $item)
                            //     ->first();
                            $fixed_inspection_record["check_order_id"] = data_get($create, "id", $create);
                            $fixed_inspection_record["fixed_check_items_id"] = $item;
                            $fixedInspectionRecord = FixedInspectionRecord::with([])->create($fixed_inspection_record);

                            log_action($fixedInspectionRecord,"月检合同订单添加固定项：ID ".data_get($fixedInspectionRecord,"id", $fixedInspectionRecord), "月检合同订单");
                        }
                    );
                    // $this->getModel()->with([])
                    //     ->where('id', '=', data_get($data, 'free_order_id'))
                    //     ->where('type', '=', 1)
                    //     ->update([
                    //         "status"=>2,
                    //         "customer_status"=>2
                    //     ]);

                    // 创建月检合同订单推送模板消息
                    $user = User::with([])->find(data_get($data, "user_id"));
                    TemplateMessageSend::sendCreateMonthCheckOrderToUser($user, $create);

                    log_action($create,"创建月检合同订单：".data_get($create,"name"),"月检合同订单");
                    return SimpleResponse::success("创建成功");
                }
            }
        );
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
            "free_order_id" => ["required", "integer"],
            "enterprise_name" => ["required", "string"],
            "building_area" => ["required", "integer"],
            "nature_id" => ["required", "integer"],
            "region_id" => ["required", "integer"],
            "name" => ["required", "string"],
            "mobile" => ["required", new MobileRule()],
            "address" => ["required", "string"],
            "fixed_duty" => ["required", "integer"],
            "worker_num" => ["required", "integer"],
            "age_limit" => ["required", "integer"],
            "free_amount" => ["nullable", "numeric"],
            "num_monthly_inspections" => ["required", "integer"],
            "money" => ["required", "numeric", "min:0.01"],
            "payment_type" => ["required", "in:1,2,3"],
            "down_payment" => ["nullable", "numeric"],
            "gift_num" => ["required", "integer"],
            "remark" => ["nullable", "string"],
            "fixed" => ["array","nullable"],
            "fixed.*" => ["integer"],
            "is_show_client_settlement" => ["nullable", "in:0,1"],
            "long"=>["nullable","numeric"],//经度
            "lat"=>["nullable","numeric"]// 纬度
        ]);

        if(is_null(data_get($data,"is_show_client_settlement"))){
            unset($data["is_show_client_settlement"]);
        }

        //if(is_null($data["long"])){
        if(is_null(data_get($data,"long"))){
            unset($data["long"]);
        }
        //if(is_null($data["lat"])){
        if(is_null(data_get($data,"lat"))){
            unset($data["lat"]);
        }


        $freeOrder = $this->getModel()->with([])->where("id", "=", data_get($data, "free_order_id"))->where("type", "=", 1)->first();
        if (!$freeOrder) return SimpleResponse::error("数据异常");

        $count = $this->getModel()->with([])->where("free_order_id", "=", data_get($data, "free_order_id"))->where("type", "=", 2)->count();
        if (0 < $count) return SimpleResponse::error("月检订单已存在");

        $data["type"] = 2;
        $data["demand_id"] = data_get($freeOrder, "demand_id");
        $data["long"] = data_get($freeOrder, "long");
        $data["lat"] = data_get($freeOrder, "lat");
        $data["is_gift"] = data_get($freeOrder, "is_gift");
        $data["user_id"] = data_get($freeOrder, "user_id");
        $data["remaining_service_num"] = ((data_get($data, "num_monthly_inspections") * 12) * data_get($data, "age_limit")) + data_get($data, "gift_num");

        //生成唯一的CODE码
        $code = Carbon::parse(now())->format("YmdHis").data_get($data, "user_id").mt_rand(100,999);
        $data['order_code'] = $code;

        return DB::transaction(
            function () use($data) {
                $create = $this->getModel()->with([])->create($data);
                if($create){
                    // 固定项
                    $fixed = collect(data_get($data, "fixed"));
                    $fixed->each(
                        function($item) use($create) {
                            // $is_exist = FixedInspectionRecord::with([])
                            //     ->where("fixed_check_items_id", "=", $item)
                            //     ->first();
                            $fixed_inspection_record["check_order_id"] = data_get($create, "id", $create);
                            $fixed_inspection_record["fixed_check_items_id"] = $item;
                            $fixedInspectionRecord = FixedInspectionRecord::with([])->create($fixed_inspection_record);

                            log_action($fixedInspectionRecord,"月检合同订单添加固定项：ID ".data_get($fixedInspectionRecord,"id", $fixedInspectionRecord), "月检合同订单");
                        }
                    );
                    $this->getModel()->with([])
                        ->where('id', '=', data_get($data, 'free_order_id'))
                        ->where('type', '=', 1)
                        ->update([
                            "status"=>2,
                            "customer_status"=>2
                        ]);

                    // 创建月检合同订单推送模板消息
                    $user = User::with([])->find(data_get($data, "user_id"));
                    TemplateMessageSend::sendCreateMonthCheckOrderToUser($user, $create);

                    log_action($create,"创建月检合同订单：".data_get($create,"name"),"月检合同订单");
                    return SimpleResponse::success("创建成功");
                }
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
        $checkOrder = $this->getModel()->with([
            "region",
            "nature",
            "fixedInspectionRecord",
            "fixedInspectionRecord.fixedCheckItems",
            "giftMaterialListRecord",
            "giftMaterialListRecord.goods",
            "giftMaterialListRecord.goods.brand",
            "paymentManagement",
            "monthCheck",
            "monthCheck.workers",
            "monthCheck.workers.worker"
        ])->findOrFail($id);

        return $checkOrder;
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
            "enterprise_name" => ["required", "string"],
            "building_area" => ["required", "integer"],
            "nature_id" => ["required", "integer"],
            "region_id" => ["required", "integer"],
            "name" => ["required", "string"],
            "mobile" => ["required", new MobileRule()],
            "address" => ["required", "string"],
            "fixed_duty" => ["required", "integer"],
            "worker_num" => ["required", "integer"],
            "age_limit" => ["required", "integer"],
            "free_amount" => ["required", "integer"],
            "num_monthly_inspections" => ["required", "integer"],
            // "settling_price" => ["required", "integer"],
            "gift_num" => ["required", "integer"],
            "remark" => ["nullable", "string"],
            "fixed" => ["array","nullable"],
            "fixed.*" => ["integer"],
            "is_show_client_settlement" => ["nullable", "in:0,1"],
            "long"=>["nullable","numeric"],//经度
            "lat"=>["nullable","numeric"]// 纬度
        ]);

        if(is_null(data_get($data,"is_show_client_settlement"))){
            unset($data["is_show_client_settlement"]);
        }

        //if(is_null($data["long"])){
        if(is_null(data_get($data,"long"))){
                unset($data["long"]);
        }
        //if(is_null($data["lat"])){
        if(is_null(data_get($data,"lat"))){
            unset($data["lat"]);
        }

        return DB::transaction(
            function () use($data, $id) {
                $find = $this->getModel()->with([])->find($id);
                if($find){
                    $old = clone $find;
                }
                if($find->update($data)){
                    // 固定项
                    $fixed = collect(data_get($data, "fixed"));
                    $fixedInspectionRecord = FixedInspectionRecord::with([])
                        ->where("check_order_id", "=", $id)
                        ->get();
                    // 获取已存在的固定项
                    $presence = $fixedInspectionRecord->filter(
                        function ($val) use($fixed) {
                            foreach ($fixed as $v) {
                                if (data_get($val, "fixed_check_items_id") == $v) {
                                    return $val;
                                }
                            }
                        }
                    );
                    // 获取不存在的固定项
                    $noPresence = FixedInspectionRecord::with([])
                        ->where("check_order_id", "=", $id)
                        ->whereNotIn('id', $presence)
                        ->get();
                    $noPresenceIds = [];
                    foreach ($noPresence as $val) {
                        $noPresenceIds[] = data_get($val, 'id');
                    }
                    // 删除不存在的固定项
                    FixedInspectionRecord::with([])->whereIn('id', $noPresenceIds)->delete();

                    $fixed->each(
                        function($item) use($id) {
                            $is_exist = FixedInspectionRecord::with([])
                                ->where("check_order_id", "=", $id)
                                ->where("fixed_check_items_id", "=", $item)
                                ->first();
                            if (!$is_exist) {
                                $fixed_inspection_record["check_order_id"] = $id;
                                $fixed_inspection_record["fixed_check_items_id"] = $item;
                                $fixedInspectionRecord = FixedInspectionRecord::with([])->create($fixed_inspection_record);

                                log_action($fixedInspectionRecord,"月检合同订单添加固定项：ID ".data_get($fixedInspectionRecord,"id", $fixedInspectionRecord), "月检合同订单");
                            }
                        }
                    );
                    log_action($find,"编辑月检合同订单：".data_get($find,"name"),"月检合同订单",$old);
                    return SimpleResponse::success("编辑成功");
                }
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
    }


    public function setIsShowClientSettlement(Request $request,$id)
    {
        $data = $this->validate($request,[
            "is_show_client_settlement"=>["required","in:0,1"]
        ]);
        $find = $this->getModel()->with([])->findOrFail($id);
        $old = clone $find;
        $find->is_show_client_settlement = data_get($data,"is_show_client_settlement");
        $find->save();
        log_action($find,"编辑月检合同订单是否客户结算金额可见：".data_get($find,"name"),"月检合同订单",$old);
        return $find;
    }

}

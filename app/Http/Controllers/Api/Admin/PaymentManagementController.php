<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\CheckOrder;
use App\Models\ContractManagement;
use App\Models\PaymentManagement;
use App\Models\SimpleResponse;
use App\Models\StreamingRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentManagementController extends SimpleController
{

    protected function getModel()
    {
        return new PaymentManagement();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with([]);

        if ($check_order_id = data_get($data, "check_order_id")) {
            $model->where("check_order_id", "=", $check_order_id);
        } else {
            throw new NoticeException("参数异常");
        }

        return $model;
    }


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

        $sum_model = clone $model;
        $total_amount = $sum_model->where("status", ">", 0)->sum("money");

        $check_order_id = $request->input("check_order_id");

        $CheckOrder = CheckOrder::with([])->find($check_order_id);

        if ($request->header('simple-page') == 'true') {
            $list = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $list = $model->paginate($request->input("per-page", 15));
        }

        $data["list"] = $list;
        $data["amount"] = [
            "contract_amount" => data_get($CheckOrder, "money"),
            "amount_received" => $total_amount,
            "amount_to_be_received" => data_get($CheckOrder, "money") - $total_amount
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
            "payment_data" => ["array", "required"],
            "payment_data.*.money" => ["required","numeric"],
            "payment_data.*.date_payable" => ["required","date_format:Y-m-d"]
        ]);

        $checkOrder = CheckOrder::with([])->where("id", data_get($data, "check_order_id"))->first();

        $total_amount = $this->getModel()->with([])->where('check_order_id', '=', data_get($data, "check_order_id"))->where("status", ">", 0)->sum("money");

        $uncollected = data_get($checkOrder, "money") - $total_amount;

        $sum_payment_data_money = 0;

        foreach (data_get($data, "payment_data") as $val) {
            $sum_payment_data_money += data_get($val, "money");
        }

        if ($uncollected < $sum_payment_data_money) throw new NoticeException("提交应付金额超出剩余应付金额");

        $user_id = data_get($checkOrder, "user_id");
        $worker_id = data_get($request->user(), "id");
        $payment_type = data_get($checkOrder, "payment_type");

        return DB::transaction(
            function () use($data, $user_id, $worker_id, $payment_type) {
                $payment_data = collect(data_get($data, "payment_data"));
                foreach ($payment_data as $v) {
                    $v["user_id"] = $user_id;
                    $v["worker_id"] = $worker_id;
                    $v["payment_type"] = $payment_type;
                    $v["check_order_id"] = data_get($data, "check_order_id");
                    $create = $this->getModel()->with([])->create($v);
                    if ($create) {
                        log_action($create, "月检合同订单-支付管理 添加：ID " . data_get($create, "id"), "月检合同订单-支付管理");
                    }
                }
                return SimpleResponse::success("添加成功");
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
        return false;
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
            "money" => ["required", "numeric"],
            "pay_date" => ["required", "date_format:Y-m-d"],
            "pay_type" => ["required", "in:1,2"]
        ]);

        $data["status"] = 2;
        $data["worker_id"] = $request->user()->id;

        return DB::transaction(
            function () use($data, $id) {

                $find = $this->getModel()->with([])->find($id);

                if (!data_get($find, "payment_order")) {
                    $data["payment_order"] = Carbon::parse(now())->format("YmdHis").data_get($find, "user_id").mt_rand(100,999);
                }

                if ($find) $old = clone $find;

                $find->update($data);

                $checkOrder = CheckOrder::with([])->find(data_get($find, "check_order_id"));
                // 未支付 || 微信支付未确定
                //if (data_get($old, "status") == 0 || (data_get($old, "status") ==0 && data_get($old, "pay_type") == 1)) {
                if (data_get($old, "status")<=2) {
                    $info["region_id"] = data_get($checkOrder, "region_id");
                    $info["check_order_id"] = data_get($checkOrder, "id");
                    $info["order_code"] = data_get($find, "payment_order");
                    $info["enterprise_name"] = data_get($checkOrder, "enterprise_name");
                    $info["name"] = data_get($checkOrder, "name");
                    $info["mobile"] = data_get($checkOrder, "mobile");
                    $info["money"] = data_get($find, "money");
                    $info["pay_type"] = data_get($find, "pay_type");
                    $info["pay_time"] = data_get($find, "pay_date");
                    $add = StreamingRevenue::with([])->create($info);
                    log_action($add, "流水收入 添加： " . data_get($checkOrder, "name"), "流水收入");
                }
                $count = PaymentManagement::with([])
                    ->where("check_order_id", "=", data_get($find, "check_order_id"))
                    ->where("status", "=", 0)
                    ->count();

                if (0 < $count) {
                    // $up["pay_status"] = 2;
                    $checkOrder->pay_status = 2;
                } else {
                    // $up["pay_status"] = 1;
                    $checkOrder->pay_status = 1;
                }
                $checkOrder->save();

                log_action($find, "月检合同订单-支付管理 确认：ID " . data_get($find, "id"),  "月检合同订单-支付管理", $old);
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

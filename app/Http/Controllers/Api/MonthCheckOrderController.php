<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\SimpleController;
use App\Models\BigFile;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SystemParameters;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;
use App\Models\CheckOrder;
use App\Models\CheckOrderComments;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\PaymentManagement;
use App\Models\PayOrder;
use App\Models\StreamingRevenue;
use EasyWeChat\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthCheckOrderController extends SimpleController
{

    protected function getPayment()
    {
        return get_wx_payment();
    }

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
            "paymentManagement" => function ($query) {
                $query->where("status", "=", 0)->whereBetween("date_payable", [Carbon::parse('today')->toDateTimeString(), Carbon::parse('+5 days')->toDateTimeString()]);
            }
        ]);

        $model->where("type", "=", 2);

        $model->where("user_id", "=", data_get($user, 'id'));

        // 检查订单状态
        if ($status = data_get($data, "status")) {
            switch ($status) {
                case 1:
                    $model->where("status", "<>", 2);
                    break;
                case 2:
                    $model->where("status", "=", 2);
                    break;
                default:
                    break;
            }
        }

        return $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
            $data = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $data = $model->paginate($request->input("per-page", 15));
        }

        return $data;
    }

    public function publicInfo()
    {
        $data = SystemParameters::with([])->select("account", "open_account_bank")->where("id", "=", 1)->first();
        return SimpleResponse::success("请求成功", $data);
    }

    public function payData(Request $request)
    {
        $data = $this->validate($request, [
            "id" => ["array","required"],
            "id.*" => ["required","integer"],
            "pay_type" => ["required","integer"],
            "open_id" => ["required", "string"]
        ]);
        
        $payList = PaymentManagement::with([])->whereIn("id", data_get($data, "id"))->get();
        return DB::transaction(
            function () use($data, $payList) {
                $money = 0;
                $payment_order = Carbon::parse(now())->format("YmdHis").mt_rand(100,999);
                foreach ($payList as $payInfo) {
                    $money += data_get($payInfo, "money");
                    if (data_get($data, "pay_type") == 2) {
                        $update["status"] = 1;
                        $update["pay_date"] = date("Y-m-d H:i:s");
                    }
                    $update["payment_order"] = $payment_order;
                    $update["pay_type"] = data_get($data, "pay_type");
                    PaymentManagement::with([])->where("id", "=", data_get($payInfo, "id"))->update($update);
                }

                if (data_get($data, "pay_type") == 1) {

                    $notify_url = get_http_host() . "/api/user/month-orders/notify-url";

                    $result = $this->getPayment()->order->unify([
                        'body' => '用户月检支付付款',
                        'out_trade_no' => $payment_order,
                        'total_fee' => $money * 100,
                        'notify_url' => $notify_url, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                        'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                        'openid' => data_get($data, "open_id")
                    ]);

                    $jssdk = $this->getPayment()->jssdk;

                    $config = $jssdk->bridgeConfig($result["prepay_id"], false); // 返回数组

                    $payData["ids"] = implode(",", data_get($data, "id"));
                    $payData["money"] = $money;
                    $payData["out_trade_no"] = $payment_order;
                    $payData["open_id"] = data_get($data, "open_id");
                    PayOrder::with([])->create($payData);

                    return SimpleResponse::success("微信支付数据", $config);
                }

                return SimpleResponse::success("支付成功");
            }
        );
    }

    public function notifyUrl()
    {
        $response = $this->getPayment()->handlePaidNotify(function ($message, $fail) {
            // 获取订单信息
            $payDataInfo = PaymentManagement::with([])
                ->where("payment_order", "=", data_get($message, "out_trade_no"))
                ->get();
            if ($payDataInfo) {
                if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                    if (data_get($message, 'result_code') === 'SUCCESS') {
                        // 用户支付成功
                        foreach ($payDataInfo as $val) {
                            $update["status"] = 2;
                            $update["pay_date"] = date("Y-m-d H:i:s");
                            PaymentManagement::with([])->where("id", "=", data_get($val, "id"))->update($update);

                            // 获取订单信息
                            $checkOrder = CheckOrder::with([])->find(data_get($val, "check_order_id"));

                            // 添加收入记录
                            $info["region_id"] = data_get($checkOrder, "region_id");
                            $info["check_order_id"] = data_get($checkOrder, "id");
                            $info["order_code"] = data_get($val, "payment_order");
                            $info["enterprise_name"] = data_get($checkOrder, "enterprise_name");
                            $info["name"] = data_get($checkOrder, "name");
                            $info["mobile"] = data_get($checkOrder, "mobile");
                            $info["money"] = data_get($val, "money");
                            $info["pay_type"] = data_get($val, "pay_type");
                            $info["pay_time"] = data_get($val, "pay_date");
                            $add = StreamingRevenue::with([])->create($info);
                            log_action($add, "流水收入 添加： " . data_get($checkOrder, "name"), "流水收入");
                            
                            $count = PaymentManagement::with([])
                                ->where("check_order_id", "=", data_get($val, "check_order_id"))
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
                        }
                    } elseif (data_get($message, 'result_code') === 'FAIL') {
                        // 用户支付失败
                    }
                    return true;
                } else {
                    return $fail('通信失败，请稍后再通知我');
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }
        });

        return $response;
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
            "monthCheck",
            "monthCheck.workers",
            "monthCheck.workers.worker",
            "userContracts",
            "userContracts.files",
            "monthlyChecklist",
            "monthlyChecklist.files",
            "paymentManagement"
        ])->findOrFail($id);
    }

    public function comment(Request $request)
    {
        $data = $this->validate($request,[
            "check_order_id"=>["required","integer"],
            "month_check_id"=>["required","integer"],
            "content"=>["required","string"],
            "files"=>["nullable","array"],
            "files.*.id"=>["nullable","integer"],
            "files.*.big_file_id"=>["required_with:files","integer"],
            "files.*.name"=>["required_with:files","string","max:255"]
        ]);
        $user = $request->user();
        return DB::transaction(function ()use($data,$user){
            $data["user_id"] = $user->id;
            $comment_model = new CheckOrderComments();
            $comment = $comment_model->with([])->create($data);
            $files = data_get($data,"files");
            if(!empty($files)){
                $files = collect($files)->map(function($item)use($comment){
                    $item['check_order_comments_id'] = $comment->id;
                    //获取文件信息
                    $file = $this->getFile($item["big_file_id"]);
                    $item['url'] = $file->url;
                    return $item;
                });
                $comment->syncFiles($files->toArray());
            }
            return SimpleResponse::success("评价成功");

        });
    }

    /**
     * 根据ID获取文件信息
     * @param Request $request
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }

    public function monthCheck($mcid)
    {
        return MonthCheck::with([
            "checkOrder",
            "workers",
            "workers.worker",
            "faultSummaryRecord",
            "faultSummaryRecord.files",
            "siteConditions",
            "siteConditions.files",
            "materialListRecord",
            "materialListRecord.goods",
            "materialListRecord.goods.brand",
            "jobContent",
            "jobContent.files",
            "checkOrderComments",
            "checkOrderComments.files"
        ])->findOrFail($mcid);
    }

    public function workRecord(Request $request)
    {
        $data = $this->validate($request, [
            "check_order_id" => ["required","integer"],
            "month_check_id" => ["required","integer"],
            "worker_id" => ["required","integer"]
        ]);

        $data =  MonthCheckWorker::with([
                "worker",
                "checkOrder",
                "monthCheck",
                "checkOrder.nature",
                "checkOrder.region",
                "monthCheckWorkerAction",
                "monthCheckWorkerAction.files",
            ])
            ->where("check_order_id", "=", data_get($data, "check_order_id"))
            ->where("month_check_id", "=", data_get($data, "month_check_id"))
            ->where("worker_id", "=", data_get($data, "worker_id"))
            ->first();
        return $data;
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
        return false;
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

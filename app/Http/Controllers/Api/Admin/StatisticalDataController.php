<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckOrder;
use App\Models\ContractManagement;
use App\Models\Demand;
use App\Models\DemandOrderStatistics;
use App\Models\FlowExpenses;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\PaymentManagement;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StatisticalDataController extends Controller
{

    // 实时统计
    public function realTime(Request $request)
    {
        $data = $this->getWhere($request);

        if ($request->input("is")) {
            return SimpleResponse::error("", $data);
        }

        $ContractManagement = ContractManagement::with([]);
        $CheckOrder = CheckOrder::with([]);
        $PaymentManagement1 = PaymentManagement::with([]);
        $PaymentManagement2 = PaymentManagement::with([]);
        $FlowExpenses1 = FlowExpenses::with([]);
        $FlowExpenses2 = FlowExpenses::with([]);
        $users = User::with([]);

        $region_id = data_get($data, "region_id");
        if (0<$region_id) {
            $ContractManagement->where('region_id', '=', $region_id);
            $CheckOrder->where('region_id', '=', $region_id);
            $FlowExpenses1->where('region_id', '=', $region_id);
            $FlowExpenses2->where('region_id', '=', $region_id);
        }

        $startDateTime = data_get($data, "startDateTime");
        $endDateTime = data_get($data, "endDateTime");
        if ($startDateTime && $endDateTime) {
            $ContractManagement->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $CheckOrder->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $PaymentManagement1->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $PaymentManagement2->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $FlowExpenses1->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $FlowExpenses2->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $users->whereBetween('created_at', [$startDateTime, $endDateTime]);
        }

        // 合同金额
        $contract_management_money = $ContractManagement->sum("money");
        // 成单数
        $check_order_num = $CheckOrder->where('type', '=', 2)->count("id");
        // 微信支付收入
        $wx_pay_money = $PaymentManagement1->where('pay_type', '=', 1)->where('status', '=', 2)->sum("money");
        // 对公支付收入
        $dg_pay_money = $PaymentManagement2->where('pay_type', '=', 2)->where('status', '=', 2)->sum("money");
        // 总收入
        $total_revenue = $wx_pay_money + $dg_pay_money;
        // 累计工时
        $work_hours = $FlowExpenses1->sum("service_time");
        // 工人结算
        $settlement = $FlowExpenses1->sum("money");
        // 新增客户
        $users_num = $users->count("id");

        $arr = [
            "contract_money" => $contract_management_money,
            "check_order_num" => $check_order_num,
            "wx_pay_money" => $wx_pay_money,
            "dg_pay_money" => $dg_pay_money,
            "total_revenue" => $total_revenue,
            "work_hours" => $work_hours,
            "settlement" => round($settlement, 2),
            "users_num" => $users_num
        ];

        return SimpleResponse::success("请求成功", $arr);
    }

    // 收支统计
    public function inExp(Request $request)
    {
        $data = $this->getWhere($request);

        $freeCheckOrder = CheckOrder::with([])->where("type", "=", 1)
            ->where("status", "=", 2);
        $monthCheckOrder = MonthCheck::with([])->where("type", "=", 2);
        $monthCheckWorker = MonthCheckWorker::with([])
            ->where("end_type", "=", 1)
            ->where("status", "=", 4);

        $region_id = data_get($data, "region_id");
        if (0<$region_id) {
            $freeCheckOrder->where("region_id", "=", $region_id);
            // $monthCheckOrder->where("region_id", "=", $region_id);
            $monthCheckOrder->whereHas(
                "CheckOrder",
                function (Builder $query) use($region_id) {
                    $query->where('region_id', '=', $region_id);
                }
            );
            $monthCheckWorker->whereHas(
                "CheckOrder",
                function (Builder $query) use($region_id) {
                    $query->where('region_id', '=', $region_id);
                }
            );
        }
        
        $startDateTime = data_get($data, "startDateTime");
        $endDateTime = data_get($data, "endDateTime");
        if ($startDateTime && $endDateTime) {
            // $startDateTime = date("Y-m-d", $startDateTime);
            // $endDateTime = date("Y-m-d", $endDateTime);
            $freeCheckOrder->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $monthCheckOrder->whereBetween('created_at', [$startDateTime, $endDateTime]);
            $monthCheckWorker->whereBetween('stop_at', [$startDateTime, $endDateTime]);
        }

        $freeCheckOrderData = $freeCheckOrder->groupBy("date")->get([
            DB::raw('DATE(created_at) as date'), 
            DB::raw('COUNT(*) as order_num')
        ]);
        $monthCheckData = $monthCheckOrder->groupBy("date")->get([
            DB::raw('DATE(created_at) as date'), 
            DB::raw('COUNT(*) as order_num')
        ]);
        $monthCheckWorkerData = $monthCheckWorker->groupBy("date")->get([
            DB::raw('DATE(stop_at) as date'), 
            DB::raw('SUM(client_settlement) as client_money'),
            DB::raw('SUM(earnings) as staff_money'), 
            DB::raw('SUM(profit) as profit_money')
        ]);

        $arr = [
            $freeCheckOrderData,
            $monthCheckData,
            $monthCheckWorkerData
        ];

        $dataArr = [];
        foreach ($arr as $val) {
            foreach ($val as $v) {
                if (!isset($dataArr[$v['date']])) {
                    $dataArr[$v['date']]['order_num'] = isset($v['order_num'])?$v['order_num']:0;
                    $dataArr[$v['date']]['client_money'] = isset($v['client_money'])?$v['client_money']:0;
                    $dataArr[$v['date']]['staff_money'] = isset($v['staff_money'])?$v['staff_money']:0;
                    $dataArr[$v['date']]['profit_money'] = isset($v['profit_money'])?$v['profit_money']:0;
                    $dataArr[$v['date']]['created_at'] = $v['date'];
                } else {
                    $dataArr[$v['date']]['order_num'] += isset($v['order_num'])?$v['order_num']:0;
                    $dataArr[$v['date']]['client_money'] += isset($v['client_money'])?$v['client_money']:0;
                    $dataArr[$v['date']]['staff_money'] += isset($v['staff_money'])?$v['staff_money']:0;
                    $dataArr[$v['date']]['profit_money'] += isset($v['profit_money'])?$v['profit_money']:0;
                    $dataArr[$v['date']]['created_at'] = $v['date'];
                }
            }
        }

        if ($dataArr) {
            $arrData = [];
            foreach ($dataArr as $value) {
                $arrData[] = $value;
            }
        } else {
            $arrData[] = [
                "order_num" => 0,
                "client_money" => "0.00",
                "staff_money" => "0.00",
                "profit_money" => "0.00",
                "created_at" => date("Y-m-d"),
            ];
        }

        return SimpleResponse::success("请求成功", $arrData);
    }
    
    // 需求统计
    public function demand(Request $request)
    {
        $data = $this->getWhere($request);

        $demand1 = Demand::with([]);
        $demand2 = Demand::with([]);
        $demand3 = Demand::with([]);
        $demand4 = Demand::with([]);

        $demandOrderStatistics = DemandOrderStatistics::with([])->select("submit_num", "process_num", "created_at");

        $region_id = data_get($data, "region_id");
        if (0<$region_id) {
            $demand1->where("region_id", "=", $region_id);
            $demand2->where("region_id", "=", $region_id);
            $demand3->where("region_id", "=", $region_id);
            $demand4->where("region_id", "=", $region_id);
        }
        
        // 未处理
        $not_processed = $demand1->where("status", "=", 0)->count("id");
        // 今日处理
        $handle_today = $demand2->where("status", "<>", 0)
            ->whereBetween("created_at", [Carbon::today(), Carbon::today()->addDay()])
            ->count("id");
        // 本月处理
        $handle_month = $demand2->where("status", "<>", 0)
            ->whereBetween("created_at", [Carbon::today()->firstOfMonth(), Carbon::today()->endOfMonth()])
            ->count("id");
        // 上月处理
        $lastMonth = Carbon::parse("-1 months");
        $handle_last_month = $demand3->where("status", "<>", 0)
            ->whereBetween("created_at", [$lastMonth->firstOfMonth(), $lastMonth->endOfMonth()])
            ->count("id");
        
        $startDateTime = data_get($data, "startDateTime");
        $endDateTime = data_get($data, "endDateTime");
        if ($startDateTime && $endDateTime) {
            $demandOrderStatistics->whereBetween('created_at', [$startDateTime, $endDateTime]);
        }
        
        // 提交需求/处理需求
        $demandOrderStatistics = $demandOrderStatistics->orderBy("id")->get();

        $arr = [
            "not_processed" => $not_processed,
            "handle_today" => $handle_today,
            "handle_month" => $handle_month,
            "handle_last_month" => $handle_last_month,
            "demandOrderStatistics" => $demandOrderStatistics
        ];

        return SimpleResponse::success("请求成功", $arr);
    }
    
    // 月检统计
    public function monthlyCheck(Request $request)
    {
        $data = $this->getWhere($request);

        $checkOrder = CheckOrder::with([])->where("type", "=", 2);

        $region_id = data_get($data, "region_id");
        if (0<$region_id) {
            $checkOrder->where("region_id", "=", $region_id);
        }
        
        $day = clone $checkOrder;
        $month = clone $checkOrder;
        $last_month = clone $checkOrder;
        $total = clone $checkOrder;
        $to_be = clone $checkOrder;
        
        // 今日下单
        $day_num = $day->whereBetween("created_at", [Carbon::today(), Carbon::today()->addDay()])->count("id");
        // 本月下单
        $month_num = $month->whereBetween("created_at", [Carbon::today()->firstOfMonth(), Carbon::today()->endOfMonth()])->count("id");
        // 上月下单
        $lastMonth = Carbon::parse("-1 months");
        $last_month_num = $last_month->whereBetween("created_at", [$lastMonth->firstOfMonth(), $lastMonth->endOfMonth()])->count("id");
        // 总完成单
        $total_num = $total->where("pay_status", "=", 1)->count("id");
        // 待支付单
        $to_be_num = $to_be->where("pay_status", "=", 0)->count("id");

        $arr = [
            "day_num" => $day_num,
            "month_num" => $month_num,
            "last_month_num" => $last_month_num,
            "total_num" => $total_num,
            "to_be_num" => $to_be_num
        ];

        return SimpleResponse::success("请求成功", $arr);
    }
    
    // 合同统计
    public function contract(Request $request)
    {
        $data = $this->getWhere($request);

        $contractManagement = ContractManagement::with([]);

        $region_id = data_get($data, "region_id");
        if (0<$region_id) {
            $contractManagement->where("region_id", "=", $region_id);
        }

        $upcoming = clone $contractManagement;
        $month = clone $contractManagement;
        $last_month = clone $contractManagement;
        $sum_num = clone $contractManagement;
        $total = clone $contractManagement;
        
        // 即将到期
        $upcoming_num = $upcoming->where("status", "=", 2)->count("id");
        // 本月签订
        $month_num = $month->whereBetween("created_at", [Carbon::today()->startOfMonth(), Carbon::today()->endOfMonth()])->count("id");
        // 上月签订
        $lastMonth = Carbon::parse("-1 months");
        $last_month_num = $last_month->whereBetween("created_at", [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()])->count("id");
        // 合同总数
        $sum_num_s = $sum_num->count("id");
        // 合同总额
        $total_money = $total->sum("money");
        
        $arr = [
            "upcoming_num" => $upcoming_num,
            "month_num" => $month_num,
            "last_month_num" => $last_month_num,
            "sum_num_s" => $sum_num_s,
            "total_money" => $total_money
        ];

        return SimpleResponse::success("请求成功", $arr);
    }
    
    // 工人统计
    public function worker(Request $request)
    {
        $data = $this->getWhere($request);

        $workers = Worker::with([])->where("status", "=", "1")->where("type", "<>", 1);

        $region_id = data_get($data, "region_id");
        if (0<$region_id) {
            $workers->where("region_id", "=", $region_id);
        }
        
        $tasks = clone $workers;
        $free = clone $workers;
        $rest = clone $workers;
        $total = clone $workers;

        // 工作中
        $tasks_num = $tasks->where("work_status", "=", 2)->count("id");
        // 空闲中
        $free_num = $free->where("work_status", "=", 1)->count("id");
        // 休息中
        $rest_num = $rest->where("work_status", "=", 0)->count("id");
        // 总人数
        $total_num = $total->count("id");
        
        $arr = [
            "tasks_num" => $tasks_num,
            "free_num" => $free_num,
            "rest_num" => $rest_num,
            "total_num" => $total_num
        ];

        return SimpleResponse::success("请求成功", $arr);
    }

    public function getWhere($request)
    {
        // 获取当前登录用户信息
        $user = $request->user();
        // 获取当前请求数据
        $data = $request->input();

        $region_id = 0;
        $startDateTime = "";
        $endDateTime = "";

        // 如果登录为区域经理，则只展示此区域数据
        if (data_get($user, "type") == 2) {
            $region_id = data_get($user, "region_id");
        } else {
            // 所属区域搜索
            if (data_get($data, "region_id")) $region_id = data_get($data, "region_id");
        }
            
        // 检查时间范围搜索
        if ($date_range = data_get($data, "date_range")) {
            $date_ranges = explode("~", $date_range);
            $startDateTime = $date_ranges[0];
            $endDateTime = $date_ranges[1];
        } else {
            // 
            if ($dateType = data_get($data, "type")) {
                switch ($dateType) {
                    // 获取当日
                    case '1':
                        $startDateTime = Carbon::today();
                        $endDateTime = Carbon::today()->addDay();
                        break;
                    // 获取当周
                    case '2':
                        $startDateTime = Carbon::today()->startOfWeek();
                        $endDateTime = Carbon::today()->endOfWeek();
                        break;
                    // 获取当月
                    case '3':
                        $startDateTime = Carbon::today()->firstOfMonth();
                        $endDateTime = Carbon::today()->endOfMonth();
                        break;
                    default:
                        break;
                }
            }
        }

        return [
            "region_id" => $region_id,
            "startDateTime" => $startDateTime,
            "endDateTime" => $endDateTime
        ];
    }
}

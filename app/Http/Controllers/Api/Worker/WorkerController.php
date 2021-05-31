<?php


namespace App\Http\Controllers\Api\Worker;


use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerActionDuration;
use App\Models\SimpleResponse;
use App\Models\Worker;
use App\Rules\MobileRule;
use App\TemplateMessageSend;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function workerInfo(Request $request)
    {
        $user  = $request->user();
        $user->load('wxWorker');
        $user->load('royalty');
        $user = $user->toArray();
        $user['sign_distance'] = config("app.sign_distance");
        return $user;
    }

    public function setMobile(Request $request)
    {
        $data = $this->validate($request,[
            "mobile"=>["required",new MobileRule()]
        ]);
        $user = $request->user();
        $user->mobile = data_get($data,"mobile");
        $user->save();
        return SimpleResponse::success("设置成功");

    }

    /**
     * @param Request $request
     * @param $id
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setWorkerStatus(Request $request,$id)
    {
        //work_status工作状态 0休息中 1空闲中 2工作中
        //pre_work_status
        $data = $this->validate($request,[
           "work_status"=>["required","in:0,1"],

        ]);

        $find = Worker::with([])->find($id);
        if(!$find) throw new NoticeException("无效操作");

        if(data_get($data,"work_status")==0){

            $this->validate($request,[
                "rest_reason"=>["required","string","max:255"]
            ]);
            $find->rest_reason = $request->input("rest_reason");
            //如果员工要休息 则需要记录之前的工作状态是空闲还是工作中
            $find->pre_work_status = $find->work_status;
            $find->work_status = data_get($data,"work_status");
        }else{
            //如果员工从休息状态中恢复 则工作状态需要恢复成之前的工作状态
            $find->work_status = $find->pre_work_status?:1;
            //之前状态是否需要改变为休息？ 员工的工作状态是随着检查订单改变 这里当恢复之后可以不用改变员工之前的工作状态字段
        }
        $find->save();
        TemplateMessageSend::sendWorkerChangeStatusToRegionWorker($find,$find->work_status);
        return SimpleResponse::success("成功");
    }

    /**
     * 员工首页数据统计
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        //工时统计
        $data["time"] = $this->getTimeStatistic($user);
        //接单统计
        $data["order"] = $this->getOrderStatistics($user);
        //收益统计
        $data["money"] = $this->getMoneyStatistics($user);
        return $data;
    }

    /**
     * 统计工时
     * @param $user
     * @return mixed
     */
    public function getTimeStatistic($user)
    {
        $today = Carbon::today();//->format("Y-m-d H:i:s");
        //今日工时
        $todayTime =MonthCheckWorkerActionDuration::with([])->where("status","=",1)
            ->where("worker_id","=",$user->id)
            ->where("start_at",">=",$today->format("Y-m-d H:i:s"))->sum("duration");
        //本月工时
        $thisMonthTime = MonthCheckWorkerActionDuration::with([])->where("status","=",1)
            ->where("worker_id","=",$user->id)
            ->where("start_at",">=",$today->firstOfMonth()->format("Y-m-d H:i:s"))->sum("duration");
        //上月工时
        $start = new Carbon('first day of last month');
        $end = new Carbon('last day of last month');
        $lastMonthTime = MonthCheckWorkerActionDuration::with([])->where("status","=",1)
            ->where("worker_id","=",$user->id)
            ->where("start_at",">=",$start->toDateTimeString())
            ->where("start_at","<=",$end->toDateTimeString())
            ->sum("duration");
        //总工时
        $totalTime = MonthCheckWorkerActionDuration::with([])->where("status","=",1)
            ->where("worker_id","=",$user->id)
            ->sum("duration");
        $data['today'] = round($todayTime/3600,2);
        $data['this_month'] = round($thisMonthTime/3600,2);
        $data['last_month'] = round($lastMonthTime/3600,2);
        $data['total'] = round($totalTime/3600,2);
        return $data;
    }

    /**
     * 接单统计
     * @param $user
     * @return mixed
     */
    public function getOrderStatistics($user)
    {
        //接单统计是用month_check_worker
        $today = Carbon::today();//->format("Y-m-d H:i:s");
        //今日接单
        $todayOrder = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
            ->where("status",'>',0)
            ->where("accept_at",">=",$today->toDateTimeString())->count();
        //本月接单
        $thisMonthOrder = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
            ->where("status",'>',0)
            ->where("accept_at",">=",$today->firstOfMonth()->toDateTimeString())->count();
        //上月接单
        $start = new Carbon('first day of last month');
        $end = new Carbon('last day of last month');
        $lastMonthOrder = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
            ->where("status",'>',0)
            ->where("accept_at",">=",$start->toDateTimeString())
            ->where("accept_at","<=",$end->toDateTimeString())->count();
        //总接单
        $total = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
            ->where("status",'>',0)->count();
        $data['today'] = $todayOrder;
        $data['this_month'] = $thisMonthOrder;
        $data['last_month'] = $lastMonthOrder;
        $data['total'] = $total;
        return $data;
    }

    public function getMoneyStatistics($user)
    {
        //收益通过month_check_worker 中的earnings统计 时间根据stop_at计算
        $today = Carbon::today();//->format("Y-m-d H:i:s");
        //今日收益
        $todayMoney = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
                ->where("stop_at",">=",$today->toDateTimeString())->sum("earnings");
        //本月收益
        $thisMonth = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
            ->where("stop_at",">=",$today->firstOfMonth()->toDateTimeString())->sum("earnings");
        //上月收益
        $start = new Carbon('first day of last month');
        $end = new Carbon('last day of last month');
        $lastMonthMoney = MonthCheckWorker::with([])->where("worker_id","=",$user->id)
            ->where("stop_at",">=",$start->toDateTimeString())
            ->where("stop_at","<=",$end->toDateTimeString())->sum("earnings");
        $totalMoney = MonthCheckWorker::with([])->where("worker_id","=",$user->id)->sum("earnings");

        $data["today"] = $todayMoney;
        $data["this_month"] = $thisMonth;
        $data["last_month"] = $lastMonthMoney;
        $data["total"] = $totalMoney;
        return $data;
    }

}

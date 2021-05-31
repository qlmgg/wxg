<?php

namespace App\Http\Controllers\Api\Worker;

use App\Events\MonthCheckWorkerActionEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\CheckOrder;
use App\Models\FixedCheckItems;
use App\Models\FixedInspectionRecord;
use App\Models\FlowExpenses;
use App\Models\JobContent;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\MonthCheckWorkerActionDuration;
use App\Models\Royalty;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Models\Worker;
use App\TemplateMessageSend;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthCheckController extends Controller
{

    public function monthCheckList(Request $request)
    {
        $this->validate($request,[
           "status_code"=>["nullable","in:0,1,2"]
        ]);
        $user = $request->user();
        $model = new MonthCheckWorker();
        $model = $model->with(['checkOrder',"monthCheck"]);
        $model = $model->where("worker_id","=",$user->id)->where("status",">",0);

        //根据状态选择 状态：-1已拒绝 0待接单 1待上门 2检查中 3暂停离场 4已完成
        if($status_code=$request->input("status_code")){
            if($status_code==1){
                //进行中
                $model = $model->where("status","<>",4);
            }else{
                //已完成
                $model = $model->where("status","=",4);
            }

        }
        $model->orderBy("id", "desc");
        if ($request->header('simple-page') == 'true') {
            return $model->simplePaginate($request->input("per-page", 15));
        } else {
            return $model->paginate($request->input("per-page", 15));
        }
    }

    /**
     * 签到/签退
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function entrySign(Request $request)
    {
        $data = $this->validate($request,[
            "sign_type"=>["required","in:1,2,3"],    //1为签到，2为暂停离场 3为结束离场
            "month_check_worker_id"=>["required","integer"],
            "address"=>["required","string","max:255"],
            "long"=>["required","numeric"],
            "lat"=>["required","numeric"],
            "files"=>["nullable","array"],
            "files.*.name"=>["required_with:files","string","max:255"],
            "files.*.big_file_id"=>["required_with:files","integer"]
        ]);

        $user = $request->user();

        $month_check_worker = MonthCheckWorker::with(['checkOrder'])->lockForUpdate()->find(data_get($data,"month_check_worker_id"));
        if($month_check_worker->worker_id != $user->id) throw new NoticeException("无效操作");

        //判断是否在用工地范围内
        $check_order = collect($month_check_worker)->get('check_order');
        $p_long = collect($check_order)->get("long");
        $p_lat = collect($check_order)->get("lat");
        $long = data_get($data,"long");
        $lat = data_get($data,"lat");
        //if(!is_in_area($p_long,$p_lat,$long,$lat,500000)) throw new NoticeException("你距离用工地大于1KM，请到指定范围内签到");
        if(!is_in_area($p_long,$p_lat,$long,$lat,config("app.sign_distance"))) throw new NoticeException("你距离用工地大于500KM，请到指定范围内签到");
        //已到用工地执行签到
        return DB::transaction(function() use($data,$user,$month_check_worker){
            $month_check = MonthCheck::with([])->find($month_check_worker->month_check_id);
            $duration_model = new MonthCheckWorkerActionDuration();
            $sign_type = data_get($data,"sign_type");
            //判断是否有上次是否签退 如果没有签退则不允许重新签到 状态为0的记录
            $not_end_duration = $duration_model->with([])
                            ->where("status","=",0)
                            ->where("worker_id","=",$user->id)
                            ->where("month_check_worker_id","=",data_get($data,"month_check_worker_id"))
                            ->orderBy("id","desc")
                            ->first();
            if($sign_type==1 && $not_end_duration) throw new NoticeException("上次的服务还未签退");
            if($sign_type!=1 && empty($not_end_duration)) throw new NoticeException("签退之前请签到");

            $event_info['address'] = data_get($data,"address");
            $event_info['long'] = data_get($data,"long");
            $event_info['lat'] = data_get($data,"lat");
            $event_info['check_order_id'] = $month_check_worker->check_order_id;
            $event_info['month_check_id'] = $month_check_worker->month_check_id;
            $event_info['month_check_worker_id'] = $month_check_worker->id;
            $event_info['worker_id'] = $user->id;
            //操作类型 1后台派单  2工人接单 3工人拒绝 4入场签到 5填写工作内容  6暂停出场签退 7结束签退 8后台异常结束签退
            //1为签到，2为暂停离场 3为结束离场
            if($sign_type==1){
                $event_info['type'] = MonthCheckWorkerAction::ENTRANCE_SIGN_TYPE;
            }elseif($sign_type==2){
                $event_info['type'] = MonthCheckWorkerAction::STOP_SIGN_TYPE;
            }elseif($sign_type==3){
                $event_info['type'] = MonthCheckWorkerAction::END_SIGN_TYPE;
            }

            $event_info['action_time'] = Carbon::now()->format("Y-m-d H:i:s");
            $event_info['files'] = data_get($data,"files");
            //$create = event(new MonthCheckWorkerActionEvent($event_info));
            $action = $this->createAction($event_info);

            $order = CheckOrder::with([])->find($month_check_worker->check_order_id);

            if($sign_type==1){  //签到
                //写入本次服务的开始时间
                $duration_info['month_check_worker_action_id'] = $action->id;
                $duration_info['check_order_id'] = $month_check_worker->check_order_id;
                $duration_info['month_check_id'] = $month_check_worker->month_check_id;
                $duration_info['month_check_worker_id'] = $month_check_worker->id;
                $duration_info['worker_id'] = $user->id;
                $duration_info['status'] = 0;
                $duration_info["start_at"] = Carbon::now()->format("Y-m-d H:i:s");
                $duration_model->with([])->create($duration_info);

                //更改check_worker状态 签到之后状态更为检查中
                $month_check_worker->status = 2;
                if(empty($month_check_worker->start_at)){
                    $month_check_worker->start_at = $duration_info["start_at"];
                }
                $month_check_worker->save();
                //更改月检的状态
                if($month_check->status==0){
                    $month_check->status=1;
                    $month_check->save();
                }

                //员工工作状态设置为工作中
                $user->work_status = 2;
                $user->save();
                //如果月检合同不是检查中状态则该为检查中状态

                if($order->status!=1){
                    $order->status=1;
                    $order->save();
                }
                $toUser = User::find($order->user_id);
                TemplateMessageSend::sendWorkerSignInToUser($toUser,$action);
                TemplateMessageSend::sendWorkerSignInToWorker($user,$action);
                //TemplateMessageSend::sendWorkerSignInToRegionWorker($user,$action);
                $this->sendWorkerSignInToRegionWorkers($user,$action);
                return SimpleResponse::success("签到成功");
            }

            if($sign_type==2){  //暂停签退
                if(!$this->isSubmitContent($month_check_worker->id)) throw new NoticeException("签退失败，请先提交工作内容");
                $now_time = Carbon::now()->format("Y-m-d H:i:s");
                $not_end_duration->stop_at = $now_time;
                $not_end_duration->duration = strtotime($now_time)-strtotime($not_end_duration->start_at);
                $not_end_duration->status =1;
                $not_end_duration->save();

                //员工工作状态设置为空闲中
                $user->work_status = 1;
                $user->save();

                //更改check_worker状态 签到之后状态更为检查中
                //状态：-1已拒绝 0待接单 1待上门 2检查中 3暂停离场 4已完成
                $month_check_worker->status = 3;
                $month_check_worker->save();
                $toUser = User::find($order->user_id);
                TemplateMessageSend::sendWorkerSignOutToUser($toUser,$action);  //发送给用户
                TemplateMessageSend::sendWorkerSignOutToWorker($user,$action);  //发送给员工
                //TemplateMessageSend::sendWorkerSignOutToRegionWorker($user,$action);  //发送给员工
                $this->sendWorkerSignOutToRegionWorkers($user,$action);
                return SimpleResponse::success("暂停签退成功");

            }

            if($sign_type==3 && $month_check_worker->status!=4){  //结束离场签退
                if(!$this->isSubmitContent($month_check_worker->id)) throw new NoticeException("签退失败，请先提交工作内容");
                $now_time = Carbon::now()->format("Y-m-d H:i:s");
                $not_end_duration->stop_at = $now_time;
                $not_end_duration->duration = strtotime($now_time)-strtotime($not_end_duration->start_at);
                $not_end_duration->status =1;
                $not_end_duration->save();

                //更改check_worker状态 签到之后状态更为检查中
                //状态：-1已拒绝 0待接单 1待上门 2检查中 3暂停离场 4已完成
                $month_check_worker->status = 4;
                $month_check_worker->end_type = 1;
                $month_check_worker->stop_at = $now_time;
                //查询所有该月检单工人信息下所有的时间记录相加 计算出总工时 然后根据工时计算出收益
                $total_duration = $duration_model->with([])->where("month_check_worker_id","=",$month_check_worker->id)
                            ->where("worker_id","=",$user->id)->sum("duration");
                $month_check_worker->service_time = $total_duration;
                //根据工人的级别查询出对应的提成金额 然后计算出总收益
                $roy = Royalty::with([])->where("level","=",$user->level)->first();
                $hours = doubleval($total_duration/60/60);
                $earnings = $hours*($roy->worker_money);
                $month_check_worker->earnings = $earnings;
                //--计算客户结算费用与盈利

                $order = CheckOrder::with([])->find($month_check_worker->check_order_id);
                $client_settlement=0;
                if($order->type==2){
                    $client_settlement = $hours*($roy->customer_money);
                }
                if($order->type==1){
                    $client_settlement = $hours*($roy->customer_money);
                }

                $profit = $client_settlement-$earnings;
                if($order->type==1){
                    $profit = 0-$earnings;
                }
                $month_check_worker->client_settlement = $client_settlement;
                $month_check_worker->profit = $profit;
                $month_check_worker->save();

                //写入收入流水列表管理
                $this->settleToFlowExpenses($action,$total_duration,$earnings,$now_time,$client_settlement,$profit);

                //员工工作状态设置为空闲中
                $user->work_status = 1;
                $user->save();

                //判断是否所有的工人都已检查完毕 如果是则将月查表更新为已完成 还要检测是否是最后一次月检 如果是最后一次月检则需要把月检合同更新为已完成
                //$month_check = MonthCheck::with([])->find($month_check_worker->month_check_id);
//                $count = MonthCheckWorker::with([])->where("status","<>",4)
//                            ->where("status","<>",-1)->where("month_check_id","=",$month_check->id)->count();
                $count = MonthCheckWorker::with([])->where("status","=",4)->where("month_check_id","=",$month_check->id)->count();

                $toUser = User::find($order->user_id);
                TemplateMessageSend::sendWorkerSignOutToUser($toUser,$action);
                TemplateMessageSend::sendWorkerSignOutToWorker($user,$action);  //发送给员工
                //TemplateMessageSend::sendWorkerSignOutToRegionWorker($user,$action);  //发送给区域经理
                $this->sendWorkerSignOutToRegionWorkers($user,$action);
                //月检信息的人数以及month_check的人数如果相同并且已经全部完成则可以判断本次月检完成
                //dd($count);
                if($count>=$month_check->worker_num){
                    //更改月检的状态为完成
                    if($month_check->status!=2){
                        $month_check->status=2;
                        $month_check->save();
                    }
                    $workers = MonthCheckWorker::with([])->where("month_check_id","=",$month_check->id)->pluck("worker_id");
                    $worker_names = Worker::with([])->whereIn("id",$workers)->pluck("name");
                    //dd($worker_names->implode(","));
                    TemplateMessageSend::sendEndMonthCheckToUser($toUser,$month_check,$worker_names->implode(","));
                    $this->sendEndMonthCheckToRegionWorkers($toUser,$month_check,$worker_names->implode(","));
                    //判断本次月检是否是合同的最后一次月检 根据年限*每月几次+赠送次数
                    if($order->type==1){    //如果是免费检查单 直接结束
                        $order->status=2;
                        $order->customer_status=2;
                        $order->save();
                        //发送最终完毕模板
                        TemplateMessageSend::sendEndCheckOrderToUser($toUser,$order);
                        //TemplateMessageSend::sendEndCheckOrderToRegionWorker($toUser,$order);
                    }else{
                        //如果是付费订单则需判断是否是最后一次月检
                        //$total = ($order->age_limit*$order->num_monthly_inspections)+$order->gift_num
                        if($order->remaining_service_num==0){
                            $order->status=2;
                            $order->customer_status=2;
                            $order->save();
                            //发送最终完毕模板
                            TemplateMessageSend::sendEndCheckOrderToUser($toUser,$order);
                            //TemplateMessageSend::sendEndCheckOrderToRegionWorker($toUser,$order);
                        }
                    }

                    //发送本次月检订单已完成
                }
                //$toUser = User::find($order->user_id);

                return SimpleResponse::success("结束签退成功");

            }
        });


        //return $month_check_worker;
    }

    public function settleToFlowExpenses($month_check_worker_action,$total_duration,$earnings,$now_time,$client_settlement,$profit)
    {
        $model = new FlowExpenses();
        //查询该操作是否已经结算过 如果已经结算过就不结算了
        $c = $model->with([])->where("month_check_worker_action_id","=",$month_check_worker_action->id)->count();
        if($c>0) throw new NoticeException("已结算过");

        $check_order = CheckOrder::with([])->find($month_check_worker_action->check_order_id);
        $worker = Worker::with([])->find($month_check_worker_action->worker_id);
        //$flow_expense['region_id'] = $worker->region_id;
        $flow_expense['region_id'] = $check_order->region_id;
        $flow_expense['check_order_id'] = $month_check_worker_action->check_order_id;
        $flow_expense['month_check_id'] = $month_check_worker_action->month_check_id;
        $flow_expense['month_check_worker_id'] = $month_check_worker_action->month_check_worker_id;
        $flow_expense['month_check_worker_action_id'] = $month_check_worker_action->id;
        $flow_expense['worker_id'] = $month_check_worker_action->worker_id;
        $flow_expense['name'] = $worker->name;
        $flow_expense['order_code'] = $check_order->order_code;
        $flow_expense['service_time'] = $total_duration;
        $flow_expense['money'] = $earnings;
        $flow_expense['settlement_time'] = $now_time;
        $flow_expense['client_settlement'] = $client_settlement;
        $flow_expense['profit'] = $profit;
        $model->with([])->create($flow_expense);

    }

    /**
     * 签退之前是否有提交工作内容
     * @param $month_check_worker_id
     * @return bool
     */
   public function isSubmitContent($month_check_worker_id)
   {
       $month_check_worker = MonthCheckWorker::with([])->find($month_check_worker_id);
       $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);

       //如果没有固定选项 则不需要填工作内容
       $records_count = FixedInspectionRecord::with([])->where("check_order_id","=",$month_check_worker->check_order_id)->count();
       if(!$records_count) return true;

       if($check_order->type==1) return true;
       //如果是正式月检合同则验证签退之前是否提交工作内容
       $job = JobContent::with([])->where("month_check_worker_id","=",$month_check_worker_id)->first();
       //预留 如果要判断是否是在本次签到以内是否有提交工作内容 可以根据最近一次为完成签退的 签到时间来判断
       if(empty($job)){
           return false;
       }else{
           return true;
       }
   }

    public function createAction($data)
    {
        return DB::transaction(function ()use($data){
            $model = new MonthCheckWorkerAction();
            $create = $model->with([])->create($data);
            $files = data_get($data,"files");
            if(!empty($files)){
                //同步上传的文件

                $files = collect($files)->map(function($item)use($create){
                    //获取file的url
                    $item['month_check_worker_action_id'] = $create->id;
                    $file = $this->getFile($item["big_file_id"]);
                    $item['url'] = $file->url;
                    return $item;
                });

                $create->syncFiles($files->toArray());
            }
            return $create;
        });
    }

    /**
     * 根据ID获取文件信息
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }

    public function jobContent(Request $request)
    {
        $data = $this->validate($request,[
           "month_check_worker_id"=>["required","integer"],
            "contents"=>["required","array"],
            "contents.*.id"=>["nullable","integer"],
            "contents.*.type"=>["required","in:1,2"],
            "contents.*.title"=>["nullable","string","max:255"],
            "contents.*.remarks"=>["nullable","string","max:255"],
            "contents.*.files"=>["nullable","array"],
            "contents.*.files.*.id"=>["nullable","integer"],
            "contents.*.files.*.name"=>["required_with:contents.*.files","string","max:255"],
            "contents.*.files.*.big_file_id"=>["required_with:contents.*.files","integer"]
        ]);
        $user = $request->user();
        $month_check_worker = MonthCheckWorker::with(['checkOrder'])->find(data_get($data,"month_check_worker_id"));
        if($month_check_worker->worker_id != $user->id) throw new NoticeException("无效操作");

        return DB::transaction(function() use($data,$user,$month_check_worker){
            $duration_model = new MonthCheckWorkerActionDuration();
            //判断是否有已签到但未签退的服务 如果不存在则属于不在服务期，应不允许提交工作内容 工作内容应该是在签到之后，签退之前提交
            $not_end_duration = $duration_model->with([])
                ->where("status","=",0)
                ->where("worker_id","=",$user->id)
                ->where("month_check_worker_id","=",data_get($data,"month_check_worker_id"))
                ->orderBy("id","desc")
                ->first();
            if(!$not_end_duration) throw new NoticeException("请先签到");


            $event_info['check_order_id'] = $month_check_worker->check_order_id;
            $event_info['month_check_id'] = $month_check_worker->month_check_id;
            $event_info['month_check_worker_id'] = $month_check_worker->id;
            $event_info['worker_id'] = $user->id;
            //操作类型 1后台派单  2工人接单 3工人拒绝 4入场签到 5填写工作内容  6暂停出场签退 7结束签退 8后台异常结束签退
            $event_info['type'] = MonthCheckWorkerAction::WORK_CONTENT_TYPE;
            $event_info['action_time'] = Carbon::now()->format("Y-m-d H:i:s");
            //$event_info['files'] = data_get($data,"files");
            $action = $this->createAction($event_info);
            //操作事件创建成功之后写入工作内容
            $contents = data_get($data,"contents");
            $contents = collect($contents)->map(function($content)use($action,$user){
                $content['check_order_id'] = $action->check_order_id;
                $content['month_check_id'] = $action->month_check_id;
                $content['month_check_worker_action_id'] = $action->id;
                $content['month_check_worker_id'] = $action->month_check_worker_id;
                $content['worker_id'] = $user->id;
                if($id = data_get($content,"id")){
                    $jobc = JobContent::with([])->find($id);
                    if($jobc){
                        $jobc->update($content);
                    }else{
                        $jobc = JobContent::with([])->Create($content);
                        $content["id"] = $jobc->id;
                    }

                }else{
                    $jobc = JobContent::with([])->Create($content);
                    $content["id"] = $jobc->id;
                }

                //同步Job文件
                $files = data_get($content,"files");
                $files = collect($files)->map(function ($item){
                    $file = $this->getFile($item['big_file_id']);
                    $item['url'] = $file->url;
                    return $item;
                });
                $jobc->syncFiles($files->toArray());
                return $content;
            });
            $ids = $contents->pluck("id");
            //查询所有不存在该ID中的记录并删除 对应的时该条月检信息员工表中的数据
            JobContent::with([])
                ->where("month_check_worker_id","=",data_get($data,"month_check_worker_id"))
                ->where("worker_id","=",$user->id)
                ->whereNotIn("id",$ids)->delete();
            return SimpleResponse::success("提交成功");

        });

    }

    public function getJobContents(Request $request)
    {
        $data = $this->validate($request,[
           //"worker_id"=>["required","integer"],
           "month_check_worker_id"=>["required","integer"]
        ]);
        $month_check_worker = MonthCheckWorker::with([])->findOrFail(data_get($data,"month_check_worker_id"));
        $check_order = CheckOrder::with([])->findOrFail($month_check_worker->check_order_id);
        $user = $request->user();
        $contents = JobContent::with(['files.bigFile'])->where("worker_id","=",$user->id)
                    ->where("month_check_worker_id","=",data_get($data,"month_check_worker_id"))->get();
        //如果不存在工作内容则从固定检查项目表中先获取固定检查相
        //$contents = FixedCheckItems::with([])->where("status","=",1)->get();
        if(empty($contents->toArray())){
            //$contents = FixedCheckItems::with([])->where("status","=",1)->get();
            $records = FixedInspectionRecord::with([])->where("check_order_id","=",$month_check_worker->check_order_id)->get();
            if($records){
                $contents = $records->map(function ($record){
                    return FixedCheckItems::with([])->find($record->fixed_check_items_id);

                });
                $contents = $contents->map(function ($item){
                    $item['type']=1;
                    unset($item["id"]);
                    return $item;
                });
            }

        }
        //return null;
        return $contents;
    }

    public function sendWorkerSignInToRegionWorkers($user,$action)
    {
        $region_workers = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$user->region_id)
            ->where("status","=",1)
            ->get();
        if(empty($region_workers)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        $region_workers->each(function ($region_worker) use ($user,$action) {
            TemplateMessageSend::sendWorkerSignInToRegionWorkers($user,$action,$region_worker);
        });

    }
    public function sendWorkerSignOutToRegionWorkers($user,$action)
    {
        $region_workers = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$user->region_id)
            ->where("status","=",1)
            ->get();
        if(empty($region_workers)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        $region_workers->each(function ($region_worker) use ($user,$action) {
            TemplateMessageSend::sendWorkerSignOutToRegionWorkers($user,$action,$region_worker);  //发送给区域经理
        });

    }

    public function sendEndMonthCheckToRegionWorkers($toUser,$month_check,$worker_names)
    {
        $region_workers = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$toUser->region_id)
            ->where("status","=",1)
            ->get();
        if(empty($region_workers)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        $region_workers->each(function ($region_worker) use ($toUser,$month_check,$worker_names) {
            TemplateMessageSend::sendEndMonthCheckToRegionWorkers($toUser,$month_check,$worker_names,$region_worker);//发送给区域经理
        });

    }

}

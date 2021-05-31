<?php


namespace App\Http\Controllers\Api\Worker;



use App\Events\MonthCheckWorkerActionEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\RejectOrderRecord;
use App\Models\SimpleResponse;
use App\Models\Worker;
use App\TemplateMessageSend;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckOrderController extends Controller
{

    /**
     * 派单列表
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator
     */
    public function orderReceiving(Request $request)
    {
        $user = $request->user();
        $model = new MonthCheckWorker();
        $model = $model->with(["worker.royalty",'checkOrder',"monthCheck"]);
        $model = $model->where("worker_id","=",$user->id)->where("status","=",0);
        $model = $model->whereHas("checkOrder",function ($query)use($user){
            //$query->where("region_id","=",$user->region_id);
        });
        $model->orderBy("id", "desc");
        if ($request->header('simple-page') == 'true') {
            return $model->simplePaginate($request->input("per-page", 15));
        } else {
            return $model->paginate($request->input("per-page", 15));
        }

    }

    /**
     * 抢单列表
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Contracts\Pagination\Paginator
     */
    public function orderGrab(Request $request)
    {
        $user = $request->user();
        $model = new MonthCheck();
        $model = $model->with(['checkOrder']);
        $model = $model->where("left_worker_num",">",0);
        //只能查看匹配区域下的订单 且已经抢过了就不再显示
        $model = $model->whereHas("checkOrder",function ($query) use($user){
            $query->where("region_id","=",$user->region_id);
        });

        $model = $model->whereDoesntHave("workers",function ($query) use($user){
            $query->where("worker_id","=",$user->id);
        });
        $model->orderBy("id", "desc");
        if ($request->header('simple-page') == 'true') {
            return $model->simplePaginate($request->input("per-page", 15));
        } else {
            return $model->paginate($request->input("per-page", 15));
        }

    }

    /**
     * 接受订单
     * @param Request $request
     * @param $month_check_id
     * @return SimpleResponse|mixed
     */
    public function confirmReceiveOrder(Request $request,$month_check_id)
    {
        $user = $request->user();
        if(!$this->isCanReceive($user)) throw new NoticeException("存在未完成的订单，不能接单。");
        //查询是否有创建工人信息表
        $month_check_worker = MonthCheckWorker::with([])
                            ->where("month_check_id","=",$month_check_id)
                            ->where("worker_id","=",$user->id)->first();
        if($month_check_worker){
            //如果存在则更新状态为1
            if($month_check_worker->status==1) throw new NoticeException("不能重复接单");
            $month_check_worker->accept_at = Carbon::now()->toDateTimeString();
            $month_check_worker->status = 1;
            $month_check_worker->save();

            $event_info['check_order_id'] = $month_check_worker->check_order_id;
            $event_info['month_check_id'] = $month_check_worker->month_check_id;
            $event_info['month_check_worker_id'] = $month_check_worker->id;
            $event_info['worker_id'] = $user->id;
            $event_info['type'] = MonthCheckWorkerAction::WORKER_ACCEPT_TYPE;
            $event_info['action_time'] = Carbon::now()->format("Y-m-d H:i:s");
            //event(new MonthCheckWorkerActionEvent($event_info));
            $action = $this->createAction($event_info);
            TemplateMessageSend::sendAcceptOrderSuccessToWorker($user,$action,1);
            TemplateMessageSend::sendAcceptOrderSuccessToRegionWorker($user,$action,1);
            return SimpleResponse::success("接单成功");
        }else{
            //如果不存在 则先判断剩余人数是否为0 如果不为0则新增一条记录并且状态为1

            return DB::transaction(function ()use($user,$month_check_id){
                $month_check = MonthCheck::with([])->lockForUpdate()->find($month_check_id);
                if($month_check->left_worker_num>0){
                    $month_check_worker_info['check_order_id'] = $month_check->check_order_id;
                    $month_check_worker_info['month_check_id'] = $month_check->id;
                    $month_check_worker_info['worker_id'] = $user->id;
                    $month_check_worker_info['status'] = 1;
                    $month_check_worker_info['type'] = 2;
                    $month_check_worker_info['accept_at'] = Carbon::now()->toDateTimeString();
                    $month_check_worker = MonthCheckWorker::with([])->create($month_check_worker_info);

                    $month_check->left_worker_num -= 1;
                    $month_check->save();

                    $event_info['check_order_id'] = $month_check->check_order_id;
                    $event_info['month_check_id'] = $month_check->id;
                    $event_info['month_check_worker_id'] = $month_check_worker->id;
                    $event_info['worker_id'] = $user->id;
                    $event_info['type'] = MonthCheckWorkerAction::WORKER_ACCEPT_TYPE;
                    $event_info['action_time'] = Carbon::now()->format("Y-m-d H:i:s");
                    //event(new MonthCheckWorkerActionEvent($event_info));
                    $action = $this->createAction($event_info);
                    TemplateMessageSend::sendAcceptOrderSuccessToWorker($user,$action,2);
                    //TemplateMessageSend::sendAcceptOrderSuccessToRegionWorker($user,$action,2);
                    $this->sendAcceptOrderSuccessToRegionWorkers($user,$action,2);
                    return SimpleResponse::success("接单成功");
                }else{
                    throw new NoticeException("手速太慢了，单子已被抢走~~~");
                }
            });

        }

    }

    public function sendAcceptOrderSuccessToRegionWorkers($user,$action,$type)
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
        $region_workers->each(function ($region_worker) use ($user,$action,$type) {
            TemplateMessageSend::sendAcceptOrderSuccessToRegionWorkers($user,$action,$type,$region_worker);
        });
    }

    /**
     * 拒绝接单
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reject(Request $request)
    {
        $data = $this->validate($request,[
            "month_check_id"=>["required","integer"],
            "reject_reason"=>["required","string","max:255"],
            "files"=>["nullable","array"],
            "files.*.name"=>["required_with:files","string","max:255"],
            "files.*.big_file_id"=>["required","integer"]
        ]);
        $user = $request->user();
        //查询是否有创建工人信息表
        $month_check_worker = MonthCheckWorker::with([])
            ->where("month_check_id","=",data_get($data,"month_check_id"))
            ->where("status","=",0)
            ->where("worker_id","=",$user->id)->first();
        if(!$month_check_worker) throw new NoticeException("无效操作");

        return DB::transaction(function() use($data,$user,$month_check_worker){
            //创建拒绝记录
            $data['worker_id'] = $user->id;
            $data['check_order_id'] = $month_check_worker->check_order_id;
            $data['month_check_worker_id'] = $month_check_worker->id;
            $month_check_worker->status = -1;
            $month_check_worker->save();
            $create = RejectOrderRecord::with([])->create($data);
            if($files = data_get($data,"files")){
                $files = collect($files)->map(function ($item) use($create){
                    //获取file的url
                    $item['reject_order_record_id'] = $create->id;
                    $file = $this->getFile($item["big_file_id"]);
                    $item['url'] = $file->url;
                    return $item;
                });
                $create->syncFiles($files->toArray());
            }

            $event_info['check_order_id'] = $month_check_worker->check_order_id;
            $event_info['month_check_id'] = $month_check_worker->month_check_id;
            $event_info['month_check_worker_id'] = $month_check_worker->id;
            $event_info['worker_id'] = $user->id;
            $event_info['type'] = MonthCheckWorkerAction::WORKER_REJECT_TYPE;
            $event_info['action_time'] = Carbon::now()->format("Y-m-d H:i:s");
            //event(new MonthCheckWorkerActionEvent($event_info));
            $action = $this->createAction($event_info);
            //TemplateMessageSend::sendAcceptOrderSuccessToWorker($user,$action);

            return SimpleResponse::success("拒绝成功");
            //如果上传文件不为空则同步文件
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

    public function checkOrderDeatail(Request $request)
    {
        $data = $this->validate($request,[
            "month_check_id"=>["required","integer"]
        ]);
        return MonthCheck::with(['checkOrder.region',"checkOrder.nature",'workers.worker'])->findOrFail(data_get($data,'month_check_id'));
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
     * 判断当前用书是否存在未完成的订单，如果存在则返回false 不存在则返回true
     * 如果是暂停离场可以再接一个单子 必须是暂停状态下 才可以再接一个单子
     * @return bool
     */
    public function isCanReceive(Worker $worker):bool
    {
        $count = MonthCheckWorker::where("worker_id","=",$worker->id)
            ->where(function ($query){
                $query->where("status","=",1)
                    ->orWhere("status","=",2);
            })->count();

        $count1 = MonthCheckWorker::with([])
            ->where("worker_id","=",$worker->id)
            ->where("status","=",3)->count();

        if(!$count && $count1<=2) return true;
        return false;
    }

}

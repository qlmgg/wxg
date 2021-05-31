<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\DemandChangeStatusEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\ActivityLog;
use App\Models\CheckOrder;
use App\Models\Demand;
use App\Models\DemandCommunication;
use App\Models\DemandOrderStatistics;
use App\Models\FreeInspect;
use App\Models\FreeInspectStaff;
use App\Models\Message;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\Region;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Models\Worker;
use App\Rules\MobileRule;
use App\TemplateMessageSend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DemandController extends SimpleController
{
    public function getModel()
    {
        return new Demand();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder
    {
        $model = $this->getModel();
        $model = $model->with(['nature','region', 'monthCheckOrder']);
        $user = Auth::user();
        // 判断登录用户是否为区域经理
        if($user->type==2){
            $model =$model->where("region_id","=",$user->region_id);
        } else {
            //根据区域搜索
            if($region_id = data_get($data,"region_id")){
                $model->where("region_id","=",$region_id);
            }
        }
        //根据企业名称搜索
        if($company_name = data_get($data,"company_name")){
            $model->where("company_name","like","%{$company_name}%");
        }

        //根据姓名搜索
        if($name = data_get($data,"name")){
            $model->where("name","like","%{$name}%");
        }
        //根据手机号码搜索
        if($mobile = data_get($data,"mobile")){
            $model->where("mobile","like","%{$mobile}%");
        }

        //根据提交时间搜索
        if($start_time = data_get($data,"start_time")){
            $model->where("created_at",">=",$start_time);
        }
        if($end_time = data_get($data,"end_time")){
            $model->where("created_at","<=",$end_time);
        }

        //根据状态搜索
        $status = data_get($data,"status");
        if(-2 < $status){
            $model->where("status","=",$status);
        }

        return $model;

    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "user_id"=>["required","integer"],
            "company_name"=>["required","string","max:255"],
            "structure_area"=>["required","integer","max:999999"],
            "nature_id"=>["required","integer"],
            "region_id"=>["required","integer"],
            //"province"=>["required","string","max:255"],
            //"city"=>["required","string","max:255"],
            //"district"=>["required","string","max:255"],
            "longitude"=>["required","numeric"],
            "latitude"=>["required","numeric"],
            "address"=>["required","string","max:255"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "user_demand"=>["nullable","string"]
        ]);

        //user_id 为登陆用户的ID
        //$user = Auth::user();
        $user = User::with([])->find(data_get($data,"user_id"));
        if(!$user) throw new NoticeException("请先选择用户");
        $data["user_id"] = $user->id;
        //所在区域根据填写的内容来匹配本地维护的最相近的数据
        $region_model = new Region();
        /*
        $province = data_get($data,"province");
        $city = data_get($data,"city");
        $district = data_get($data,"district");
        $region = $region_model->with([])->where("province_text","like","%{$province}%")
            ->where("city_text","like","%{$city}%")
            ->where("district_text","like","%{$district}%")->first();
        */
        $region = $region_model->with([])->find(data_get($data,"region_id"));
        if(!$region) throw new NoticeException("没有可匹配的区域");
        $data['region_id'] = $region->id;
        $data['province_text'] = $region->province_text;
        $data['province_code'] = $region->province_code;
        $data['city_text'] = $region->city_text;
        $data['city_code'] = $region->city_code;
        $data['district_text'] = $region->district_text;
        $data['district_code'] = $region->district_code;

        //生成唯一的CODE码
        $code = Carbon::parse(now())->format("YmdHis").$data['user_id'].mt_rand(100,999);
        $data['code'] = $code;
        //return $code;
        $create = $this->getModel()->with([])->create($data);
        if($create){
            //写入用户区域ID
            $user->region_id = $region->id;
            $user->save();
            // 需求统计数据
            $startDateTime = Carbon::today();
            $endDateTime = Carbon::today()->addDay();
            $now_data = DemandOrderStatistics::with([])
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->first();
            if ($now_data) {
                $submit_num = $now_data["submit_num"] + 1;
                DemandOrderStatistics::with([])
                    ->where("created_at", "=", data_get($now_data, "created_at"))
                    ->update(["submit_num"=>$submit_num]);
            } else {
                DemandOrderStatistics::with([])->create(["submit_num"=>1]);
            }
            //TemplateMessageSend::sendDemandToRegionWorker($create);
            $this->sendDemandToRegionWorkers($create);
            log_action($create, "添加需求：" . data_get($create, "name"), ActivityLog::MODULE_NAME_DEMAND);
            return SimpleResponse::success("添加成功");
        }
        return SimpleResponse::error("添加失败");
    }

    public function sendDemandToRegionWorkers($demand)
    {
        $region_workers = Worker::with([])->where("type","=",2)->where("region_id","=",$demand->region_id)->get();
        $region_workers->each(function ($region_worker) use ($demand) {
            //TemplateMessageSend::sendWorkerSignInToRegionWorkers($user,$action,$region_worker);
            TemplateMessageSend::sendDemandToRegionWorkers($demand,$region_worker);
        });
    }

    public function testSendDemandToRegionWorker()
    {
        $demand = Demand::with([])->find(1);
        $res = TemplateMessageSend::sendDemandToRegionWorker($demand);
        dd($res);
    }

    /**
     * 备份
     * @param Request $request
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function oldStore(Request $request)
    {
        $data = $this->validate($request,[
            "company_name"=>["required","string","max:255"],
            "structure_area"=>["required","integer","max:999999"],
            "nature_id"=>["required","integer"],
            "province"=>["required","string","max:255"],
            "city"=>["required","string","max:255"],
            "district"=>["required","string","max:255"],
            "address"=>["required","string","max:255"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()]
        ]);
        //user_id 为登陆用户的ID
        //测试默认认为user_id =1
        $data["user_id"] = 1;
        //所在区域根据填写的内容来匹配本地维护的最相近的数据
        $region_model = new Region();
        $province = data_get($data,"province");
        $city = data_get($data,"city");
        $district = data_get($data,"district");
        $region = $region_model->where("province_text","like","%{$province}%")
            ->where("city_text","like","%{$city}%")
            ->where("district_text","like","%{$district}%")->first();

        if(!$region) return SimpleResponse::error("没有可匹配的区域");
        $data['region_id'] = $region->id;
        $data['province_text'] = $region->province_text;
        $data['province_code'] = $region->province_code;
        $data['city_text'] = $region->city_text;
        $data['city_code'] = $region->city_code;
        $data['district_text'] = $region->district_text;
        $data['district_code'] = $region->district_code;

        //生成唯一的CODE码
        $code = Carbon::parse(now())->format("YmdHis").$data['user_id'].mt_rand(100,999);
        $data['code'] = $code;
        //return $code;
        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create, "添加需求：" . data_get($create, "name"), ActivityLog::MODULE_NAME_DEMAND);
            return SimpleResponse::success("添加成功");
        }
        return SimpleResponse::error("添加失败");
    }

    public function store1(Request $request)
    {
        $data = $this->validate($request,[
            "company_name"=>["required","string","max:255"],
            "structure_area"=>["required","integer","max:999999"],
            "nature_id"=>["required","integer"],
            "province"=>["required","string","max:255"],
            "city"=>["required","string","max:255"],
            "district"=>["required","string","max:255"],
            "address"=>["required","string","max:255"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()]
        ]);
        //user_id 为登陆用户的ID
        //测试默认认为user_id =1
        $data["user_id"] = 1;
        //所在区域根据填写的内容来匹配本地维护的最相近的数据
        $region_model = new Region();
        $province = data_get($data,"province");
        $city = data_get($data,"city");
        $district = data_get($data,"district");
        $region = $region_model->where("province_text","like","%{$province}%")
            ->where("city_text","like","%{$city}%")
            ->where("district_text","like","%{$district}%")->first();

        if(!$region) return SimpleResponse::error("没有可匹配的区域");
        $data['region_id'] = $region->id;
        $data['province_text'] = $region->province_text;
        $data['province_code'] = $region->province_code;
        $data['city_text'] = $region->city_text;
        $data['city_code'] = $region->city_code;
        $data['district_text'] = $region->district_text;
        $data['district_code'] = $region->district_code;

        //生成唯一的CODE码
        $code = Carbon::parse(now())->format("YmdHis").$data['user_id'].mt_rand(100,999);
        $data['code'] = $code;
        //return $code;
        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create, "添加需求：" . data_get($create, "name"), ActivityLog::MODULE_NAME_DEMAND);
            return SimpleResponse::success("添加成功");
        }
        return SimpleResponse::error("添加失败");
    }

    public function show($id)
    {
        return $this->getModel()->with(["region","nature"])->findOrFail($id);
    }

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "company_name"=>["required","string","max:255"],
            "structure_area"=>["required","integer","max:999999"],
            "nature_id"=>["required","integer"],
            "address"=>["required","string","max:255"],
            "longitude"=>["nullable","numeric"],
            "latitude"=>["nullable","numeric"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "user_demand"=>["nullable","string","max:255"],
            "communication"=>["array","required"],
            "communication.status"=>["required","in:1,-1"],
            "communication.door_at"=>["nullable","date_format:Y-m-d H:i"],
            "communication.content"=>["nullable","string","max:255"]
        ]);

        if(is_null(data_get($data,"longitude"))){
            unset($data["longitude"]);
        }
        if(is_null(data_get($data,"latitude"))){
            unset($data["latitude"]);
        }

        return DB::transaction(function()use($request,$data,$id){
            //更新需求表
            $find = $this->getModel()->with([])->find($id);
            if(!$find) return SimpleResponse::error("无效操作");
            //if($find->status==-1) return SimpleResponse::error("需求已作废");
            $old = clone $find;
            if($find->update($data)){
                //如果有沟通内容则新增沟通内容
                if(!empty(data_get($data,"communication.door_at")) || !empty(data_get($data,"communication.content")) || -1==data_get($data,"communication.status")){
                //if(!empty(data_get($data,"communication"))){
                    if(-1!=data_get($data,"communication.status")){
                        if(data_get($data,"communication.door_at")<Carbon::now()->toDateTimeString()) throw new NoticeException("上门时间不能小于当前时间");
                    }

                        $communication_info = data_get($data,"communication");
                        //$communication_info['admin_user_id'] = $request->user()->id;
                        $communication_info['demand_id'] = $find->id;

                        $user = Auth::guard("worker")->user();
                        $communication_info['communicator_id'] = $user->id;
                        $communication_info['communicator_type'] = get_class($user);

                        $communication_model = new DemandCommunication();
                        $create = $communication_model->with([])->create($communication_info);
                        //根据沟通状态更新需求表状态
                        if($create){
                            //如果沟通的状态为继续沟通 需求更改为状态 1 待上门检查 -1作废
                            if($create->status==1 || $create->status==-1){
                                if($find->status!=2){
                                    event(new DemandChangeStatusEvent($find,$create->status));
                                }
                            }
                        }
                }
                //当需求更改以后，同时更改免费检查单和检查单的公司名称，联系人，及联系方式
                $o_count = CheckOrder::with([])->where("demand_id","=",$find->id)->count();
                if($o_count>0){
                    //更改订单信息
                    CheckOrder::with([])->where("demand_id","=",$find->id)->update([
                        "enterprise_name"=>$find->company_name,
                        "name"=>$find->name,
                        "mobile"=>$find->mobile
                    ]);
                }

                log_action($find, "操作需求：" . data_get($find, "name"), ActivityLog::MODULE_NAME_DEMAND,$old);
                return SimpleResponse::success("操作成功");
            }
            return SimpleResponse::error("操作失败");

        });

    }

    public function userOption(Request $request)
    {
        $model = new User();
        $model = $model->with([]);
        if($text = data_get($request,"text")){
            $model->where("name","like","%{$text}%");
        }
        return $model->get(["id as value","name as text","type"]);
    }


    public function createFreeInspect(Request $request)
    {
        $data = $this->validate($request,[
            "demand_id"=>["required","integer"],
            "enterprise_name"=>["required","string","max:255"],
            "building_area"=>["required","integer","max:999999"],
            "nature_id"=>["required","integer"],
            "region_id"=>["required","integer"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "address"=>["required","string","max:255"],
            "number"=>["required","integer","max:999999"],
            "workers"=>["array","required"],
            "workers.*"=>["integer"],
            "door_time"=>["required","date_format:Y-m-d H:i"],
            "remark"=>["nullable","string","max:255"]
        ]);

        if(data_get($data,"door_time")<Carbon::now()->toDateTimeString()) throw new NoticeException("上门时间不能小于当前时间");

        //如果时区域经理那么创建时需要验证所选区域是否与区域经理相同
        $user = Auth::user();
        if($user->type==2 && data_get($data,"region_id")!=$user->region_id){
            throw new NoticeException("区域不匹配");
        }
        //查找需求
        $demand = Demand::with([])->find(data_get($data,"demand_id"));
        if(!$demand) throw new NoticeException("需求不存在");
        //提交需求用户ID
        $data['user_id'] = $demand->user_id;
        $data['order_code'] = $code = Carbon::parse(now())->format("YmdHis").$data['user_id'].$data['demand_id'].mt_rand(100,999);
        //用工人数与选择的工人匹配
        if(data_get($data,"number")<=0){
            throw new NoticeException("请输入用工人数");
        }
        if(data_get($data,"number")!=count(data_get($data,"workers"))){
            throw new NoticeException("用工人数不匹配");
        }
        //如果已经创建过免费检单则不再创建
        $count = CheckOrder::with([])
                ->where("type","=",1)
                ->where("demand_id","=",data_get($data,"demand_id"))->count();
        //if($count>0) throw new NoticeException("该需求已创建过免费订单");

        collect(data_get($data,"workers"))->map(function ($w_id){
            $worker = Worker::with([])->find($w_id);
            if($worker->work_status==0){
                throw new NoticeException("员工：".$worker->name."处于休息中，不可派单");
            }
        });

        //创建免费月检合同已经月检信息
        return DB::transaction(function()use($data,$demand){
            //创建免费月检合同
            $data['type'] = 1;
            $data['long'] = $demand->longitude;
            $data['lat'] = $demand->latitude;
            $data['worker_num'] = data_get($data,"number");
            $check_order = CheckOrder::with([])->create($data);
            $demand->door_at = data_get($data,"door_time"); //上门时间
            $demand->save();
            //创建月检记录
            $month_check_info['check_order_id'] = $check_order->id;
            $month_check_info['worker_num'] = data_get($data,"number");
            $month_check_info['door_time'] = data_get($data,"door_time");
            $month_check_info['remark'] = data_get($data,"remark");
            //dd($month_chek_info);
            $month_check = MonthCheck::with([])->create($month_check_info);

            $workers = collect(data_get($data,"workers"));
            $workers = $workers->unique();
            if($data['worker_num']!=count($workers)) throw new NoticeException("用工人数不匹配");
            //dd($workers);
            $worker_names = Worker::with([])->whereIn("id",$workers)->pluck("name");

            $workers->each(function ($item) use($month_check){
                //判断是否有免费检查员工信息 如果没有则新建
                $staff = MonthCheckWorker::with([])
                        ->where("month_check_id","=",$month_check->id)
                        ->where("worker_id","=",$item)->first();
                if(!$staff){
                    $staff_info['check_order_id'] = $month_check->check_order_id;
                    $staff_info['month_check_id'] = $month_check->id;
                    $staff_info["worker_id"] = $item;
                    $staff_info["type"] = 1;
                    $monthCheckWorker = MonthCheckWorker::with([])->create($staff_info);

                    // 添加派单记录
                    $monthCheckWorkerActionsInfo["check_order_id"] = $month_check->check_order_id;
                    $monthCheckWorkerActionsInfo["month_check_worker_id"] = $monthCheckWorker->id;
                    $monthCheckWorkerActionsInfo["month_check_id"] = $month_check->id;
                    $monthCheckWorkerActionsInfo["worker_id"] = $item;
                    $monthCheckWorkerActionsInfo["type"] = 1;
                    $monthCheckWorkerActionsInfo["action_time"] = date("Y-m-d H:i:s");
                    $action = MonthCheckWorkerAction::with([])->create($monthCheckWorkerActionsInfo);

                    //新建之后发送消息模板
                    $worker = Worker::with([])->find($item);
                    TemplateMessageSend::sendOrderToWorker($worker,$action);
                    //$this->sendWorkSignOutToUser($worker,$monthCheckWorker);
                    log_action($worker, "创建免费订单-派单给员工：" . data_get($worker, "name"), ActivityLog::MODULE_NAME_DEMAND,$worker);

                }
            });

            //dd($worker_names->implode(","));
            $toUser = User::find($check_order->user_id);
            TemplateMessageSend::sendCreateFreeCheckOrderToUser($toUser,$month_check,$worker_names->implode(","));
            //创建成功之后将需求订单的状态该为2成功
            $old = clone $demand;
            event(new DemandChangeStatusEvent($demand,2));

            // 需求统计数据
            $startDateTime = Carbon::today();
            $endDateTime = Carbon::today()->addDay();
            $now_data = DemandOrderStatistics::with([])
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->first();
            if ($now_data) {
                $process_num = $now_data["process_num"] + 1;
                DemandOrderStatistics::with([])
                ->where("created_at", "=", data_get($now_data, "created_at"))
                ->update(["process_num"=>$process_num]);
            } else {
                DemandOrderStatistics::with([])->create(["process_num"=>1]);
            }


            log_action($demand, "创建免费订单：" . data_get($demand, "name"), ActivityLog::MODULE_NAME_DEMAND,$old);
            return SimpleResponse::success("操作成功");

        });

    }



    /**
     * 获取公众号
     * @return \EasyWeChat\OfficialAccount\Application
     */
    protected static function getUserApp()
    {
        return get_official_account();
    }


    public static function sendWorkSignOutToUser(Worker $user,$action)
    {
        $app = self::getUserApp();

        // todo  通过 $user 获取openid
        $time = now()->format("Y-m-d H:i:s");

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        $officialOpenId = "o3ue06NzwLoFDqPeCaXbvNK9v1v0";
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'data' => [
                    "first" => "平台派单", // 详细内容
                    "keyword1" => $user->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => "成都 天府三街", // 地点
                    "keyword4" => "平台派单", // 类型
                    "keyword5" => "备注备注", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签退",
            "content" => "员工xxx 于 ".$time." 签退, 如有疑问，请与平台进行联系。",
            "type" => 2
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

}

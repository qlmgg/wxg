<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Models\ActivityLog;
use App\Models\Demand;
use App\Models\DemandOrderStatistics;
use App\Models\Nature;
use App\Models\Region;
use App\Models\SimpleResponse;
use App\Models\SystemParameters;
use App\Models\Worker;
use App\Rules\MobileRule;
use App\TemplateMessageSend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DemandController extends SimpleController
{
    public function getModel()
    {
        return new Demand();
    }

    public function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {
        $user = Auth::user();
        $model =  $this->getModel()->with(['region']);
        $model = $model->where("user_id","=",$user->id);
        return $model;
    }

    public function isInArea(Request $request)
    {
        $data = $this->validate($request,[
            "province"=>["required","string","max:255"],
            "city"=>["required","string","max:255"],
            "district"=>["required","string","max:255"],
        ]);

        //所在区域根据填写的内容来匹配本地维护的最相近的数据
        $region_model = new Region();
        $province = data_get($data,"province");
        $city = data_get($data,"city");
        $district = data_get($data,"district");
        $region = $region_model->with([])->where("province_text","like","%{$province}%")
            ->where("city_text","like","%{$city}%")
            ->where("district_text","like","%{$district}%")->first();
        //dd($region);
        if(!$region){
            return SimpleResponse::error("没有可匹配的区域");
        }else{
            return SimpleResponse::success("区域可匹配");
        }


    }

    public function getSystemParameters(Request $request)
    {
        $parameter = SystemParameters::with([])->first();
        if(!$parameter) return ["activity"=>null];
        return ["activity"=>$parameter->activity_description];
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "company_name"=>["required","string","max:255"],
            "structure_area"=>["required","integer","max:999999"],
            "nature_id"=>["required","integer"],
            "province"=>["required","string","max:255"],
            "city"=>["required","string","max:255"],
            "district"=>["required","string","max:255"],
            "longitude"=>["required","numeric"],
            "latitude"=>["required","numeric"],
            "address"=>["required","string","max:255"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "user_demand"=>["nullable","string"]
        ]);
        //user_id 为登陆用户的ID
        $user = Auth::user();
        if(!$user) throw new NoticeException("请先登陆");
        $data["user_id"] = $user->id;
        //所在区域根据填写的内容来匹配本地维护的最相近的数据
        $region_model = new Region();
        $province = data_get($data,"province");
        $city = data_get($data,"city");
        $district = data_get($data,"district");
        $region = $region_model->with([])->where("province_text","like","%{$province}%")
            ->where("city_text","like","%{$city}%")
            ->where("district_text","like","%{$district}%")->first();

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

    public function show($id)
    {
        return $this->getModel()->with(["region","nature"])->findOrFail($id);
    }

    public function natureOptions(Request $request)
    {
        $model = new Nature();
        $model = $model->with([]);
        if($text=data_get($request,"text")){
            $model->where("name","like","%{$text}%");
        }
        return $model->where("status","=",1)->get(["id as value","name as text"]);
    }

    public function sendDemandToRegionWorkers($demand)
    {
        $region_workers = Worker::with([])->where("type","=",2)->where("region_id","=",$demand->region_id)->get();
        $region_workers->each(function ($region_worker) use ($demand) {
            //TemplateMessageSend::sendWorkerSignInToRegionWorkers($user,$action,$region_worker);
            TemplateMessageSend::sendDemandToRegionWorkers($demand,$region_worker);
        });
    }

}

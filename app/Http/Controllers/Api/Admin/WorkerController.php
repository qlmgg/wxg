<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\WorkerRoleSyncEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\ActivityLog;
use App\Models\SimpleResponse;
use App\Models\Worker;
use App\Models\WxUser;
use App\Models\WxWorker;
use App\Rules\MobileRule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkerController extends SimpleController
{
    //
    protected function getModel()
    {
        return new Worker();
    }

    protected $orderBy = [
        [
            'column' => 'id',
            'direction' => 'desc'
        ]
    ];

    function secToTime($sec){

        $sec = round($sec/60);
        if ($sec >= 60){
            $hour = floor($sec/60);
            $min = $sec%60;
            $res = $hour.'小时 ';
            $min != 0  &&  $res .= $min.'分';
        }else{
            $res = $sec.'分钟';
        }
        return $res;
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
            $page = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $page = $model->paginate($request->input("per-page", 15));
        }
        $page = $page->toArray();
        $data = data_get($page,"data");
        $data = collect($data)->map(function ($item){
            $item['order_time'] = $this->secToTime($item["duration_sum"]);
            return $item;
        });
        $page["data"] = $data;
        return $page;
    }


    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with(["region","wxWorker.wxOfficialUser"]);//累计工时，累计接单
        $model = $model->withCount(["monthCheckWorkers"=>function($query){
            $query->where("status",">","0");
        },"monthCheckWorkerActionDurations as duration_sum"=>function($query){
            $query->where("status","=",1)->select(DB::raw("sum(duration) as duration_sum"));
        }]);


        $user = Auth::user();
        if($user->type==2){
            $model =$model->where("region_id","=",$user->region_id);
        }

        //根据姓名搜索
        if($name = data_get($data,"name")){
            $model->where("name","like","%{$name}%");
        }
        //根据手机号码搜索
        if($mobile = data_get($data,"mobile")){
            $model->where("mobile","like","%{$mobile}%");
        }
        //根据区域搜索
        if($region_id = data_get($data,"region_id")){
            $model->where("region_id","=",$region_id);
        }
        //根据员工状态搜索
        if(-1 < ($status = data_get($data,"status"))){
            $model->where("status","=",$status);
        }
        //根据工作状态搜索
        if(-1 < ($work_status = data_get($data,"work_status"))){
            $model->where("work_status","=",$work_status);
        }

        return $model;
    }

    /**
     * @param Request $request
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "entry_at"=>["required","date_format:Y-m-d"],
            "region_id"=>["required","integer"],
            "level"=>["required","integer"],
            "status"=>["required","in:0,1"],
            "openid"=>["nullable"]

        ]);

        $user = Auth::user();
        if($user->type==2){
            $data['region_id'] =$user->region_id;
        }else{
            $this->validate($request,[
                "region_id"=>["required","integer"]
            ]);
            $data["region_id"] = $request->input("region_id");
        }
        $data['type']=3;
        //手机号是否重复
        $count = $this->getModel()->with([])->where("mobile","=",data_get($data,"mobile"))->count();
        if($count){
            throw new NoticeException("手机号已存在");
        }
        //对应的openid是否有效
        if(!is_null(data_get($data,"openid"))){
            $w_model = new WxWorker();
            $o_count = $w_model->with([])->where("mobile","=",data_get($data,"mobile"))
                ->where("openid","=",data_get($data,"openid"))->count();
            if($o_count<=0){
                throw new NoticeException("无效数据");
            }
        }

        $create = $this->getModel()->with([])->create($data);

        event(new WorkerRoleSyncEvent($create));

        if($create){
            log_action($create,"添加工人：".data_get($create,"name"),ActivityLog::MODULE_NAME_WORKER);
            return SimpleResponse::success("添加成功");
        }

        return SimpleResponse::error("添加失败");
    }

    /**
     * @param $id
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function show($id)
    {
        return $this->getModel()->with(['wxWorker'])->findOrFail($id);
    }

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "entry_at"=>["required","date_format:Y-m-d"],
            //"region_id"=>["required","integer"],
            "level"=>["required","integer"],
            "status"=>["required","in:0,1"],
            "openid"=>["nullable"]
        ]);

        $user = Auth::user();
        if($user->type==2){
            $data['region_id'] =$user->region_id;
        }else{
            $this->validate($request,[
                "region_id"=>["required","integer"]
            ]);
            $data["region_id"] = $request->input("region_id");
        }

        //手机号是否重复
        $count = $this->getModel()->with([])
                ->where("mobile","=",data_get($data,"mobile"))
                ->where("id","<>",$id)
                ->count();
        if($count){
            throw new NoticeException("手机号已存在");
        }
        //对应的openid是否有效
        if(!is_null(data_get($data,"openid"))){
            $w_model = new WxWorker();
            $o_count = $w_model->with([])->where("mobile","=",data_get($data,"mobile"))
                ->where("openid","=",data_get($data,"openid"))->count();
            if($o_count<=0){
                throw new NoticeException("无效数据");
            }
        }

        $find = $this->getModel()->with([])->find($id);

        event(new WorkerRoleSyncEvent($find));

        if($find){
            $old = clone $find;
        }
        if($find->update($data)){
            log_action($find,"编辑工人：".data_get($find,"name"),ActivityLog::MODULE_NAME_WORKER,$old);
            return SimpleResponse::success("编辑成功");
        }

        return SimpleResponse::error("编辑失败");

    }

    public function destroy($id)
    {
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find && $find->delete()){
            log_action($find,"删除工人：".data_get($find,"name"),ActivityLog::MODULE_NAME_WORKER,$old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }

    public function setStatus(Request $request,$id)
    {
        $data = $this->validate($request,[
           "status"=>["required","in:0,1"]
        ]);
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
            $find->status = data_get($data,"status");
            if($find->save()){
                log_action($find,"设置工人状态：".data_get($find,"name").data_get($find,"status_text"),ActivityLog::MODULE_NAME_WORKER,$old);
                return SimpleResponse::success("设置成功");
            }
            return SimpleResponse::error("设置失败");
        }


        return SimpleResponse::error("设置失败");
    }

    public function getWxInfo(Request $request){
        $data = $this->validate($request,[
            "mobile"=>["required","string","max:255"]
        ]);
        $mobile = data_get($data,"mobile");
        //根据手机号码搜索微信信息
        return WxWorker::with([])->where("mobile","like","%{$mobile}%")->limit(10)->get();

    }

    public function options(Request $request)
    {
        $model = $this->getModel();
        $model = $model->with([]);
        $model = $model->where("status","=",1)->where("type","=",3);
        if ($name = $request->input("text")) {
            $model->where("name", "like", "%{$name}%");
        }

        if ($region_id = $request->input("region_id")) {
            $model->where("region_id", "=", $region_id);
        }

        return $model->get(["id as value", "name as text"]);
    }

}

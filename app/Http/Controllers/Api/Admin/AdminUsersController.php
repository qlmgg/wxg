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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminUsersController extends SimpleController
{
    //
    protected function getModel()
    {
        return new Worker();
    }

    protected function search(Request $request): Builder
    {
        $user = $request->user();
        if (data_get($user, "type") != 1) {
            throw new NoticeException("无操作权限");
        }
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with(["region", "role"]);//累计工时，累计接单

        $model->where("role_id", "<>", "null");
        //根据姓名搜索
        if($name = data_get($data,"name")) $model->where("name","like","%{$name}%");
        //根据手机号码搜索
        if($mobile = data_get($data,"mobile")) $model->where("mobile","like","%{$mobile}%");
        //根据员工状态搜索
        if($status = data_get($data,"status")) $model->where("status","=",$status);

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
            "role_id"=>["required","integer"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "password"=>["required","string"],
            "region_id"=>["nullable","integer"],
            "status"=>["required","in:0,1"],
            "is_worker"=>["required","in:0,1"],
            "openid"=>["nullable"]
        ]);

        //手机号是否重复
        $count = $this->getModel()->with([])
            ->where("mobile","=",data_get($data,"mobile"))
            ->count();
        if($count){
            throw new NoticeException("手机号已存在");
        }

        if (!is_null(data_get($data,"region_id"))) {
            // 所属区域是否存在经理
            $worker = $this->getModel()->with([])->where("region_id","=",data_get($data,"region_id"))->where("type", "=", 2)->where("status", "=", "1")->count();
            if(0<$worker){
                throw new NoticeException("该区域已有区域经理");
            }
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

        // 判断是否为区域经理
        if (data_get($data, "role_id") == 2) {
            $data["type"] = data_get($data, "role_id");
        } else {
            // 后台管理员
            $data["type"] = 1;
        }

        // 密码加密
        $data["password"] = Hash::make(data_get($data, "password"));

        $create = $this->getModel()->with([])->create($data);

        event(new WorkerRoleSyncEvent($create));

        if($create){
            log_action($create,"添加管理员：".data_get($create,"name"),"管理员管理");
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
        return $this->getModel()->with(["wxWorker"])->findOrFail($id);
    }

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "role_id"=>["required","integer"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "password"=>["required","string"],
            "region_id"=>["nullable","integer"],
            "status"=>["required","in:0,1"],
            "is_worker"=>["required","in:0,1"],
            "openid"=>["nullable"]
        ]);

        //手机号是否重复
        $count = $this->getModel()->with([])
                ->where("mobile","=",data_get($data,"mobile"))
                ->where("id","<>",$id)
                ->count();
        if($count){
            throw new NoticeException("手机号已存在");
        }

        if (!is_null(data_get($data,"region_id"))) {
            $adminUser = $this->getModel()->with([])->find($id);
            if (data_get($adminUser, "region_id") != data_get($data, "region_id")) {
                // 所属区域是否存在经理
                $worker = $this->getModel()->with([])->where("region_id","=",data_get($data,"region_id"))->where("type", "=", 2)->where("status", "=", "1")->count();
                if(0<$worker){
                    throw new NoticeException("该区域已有区域经理");
                }
            }
        }
        //对应的openid是否有效
        if(!is_null(data_get($data,"openid"))){
            $w_model = new WxWorker();
            $o_count = $w_model->with([])
                ->where("mobile","=",data_get($data,"mobile"))
                ->where("openid","=",data_get($data,"openid"))->count();
            if($o_count<=0){
                throw new NoticeException("无效数据");
            }
        }

        // 判断是否为区域经理
        if (data_get($data, "role_id") == 2) {
            $data["type"] = data_get($data, "role_id");
        } else {
            // 后台管理员
            $data["type"] = 1;
        }

        // 密码加密
        $data["password"] = Hash::make(data_get($data, "password"));

        $find = $this->getModel()->with([])->find($id);

        event(new WorkerRoleSyncEvent($find));

        if($find){
            $old = clone $find;
        }

        if($find->update($data)){
            log_action($find,"编辑管理员：".data_get($find,"name"),"管理员管理",$old);
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
            log_action($find,"删除工人：".data_get($find,"name"),"管理员管理",$old);
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
                log_action($find,"设置管理员状态：".data_get($find,"name").data_get($find,"status_text"),"管理员管理",$old);
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
        $model = $model->where("status","=",1);
        if ($name = $request->input("text")) {
            $model->where("name", "like", "%{$name}%");
        }

        return $model->get(["id as value", "name as text"]);
    }

}

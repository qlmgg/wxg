<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Models\ActivityLog;
use App\Models\Royalty;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\SimpleController;

class RoyaltyController extends SimpleController
{
    protected function getModel()
    {
        return new Royalty();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder
    {

        return $this->getModel()->with([]);

    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
           "level"=>["required","integer"],
            "customer_money"=>["required","numeric","max:99999999.99"],
            "worker_money"=>["required","numeric","max:99999999.99"],
            "worker_money_for_customer"=>["nullable","numeric","max:99999999.99"],
            "status"=>["required","in:0,1"]
        ]);
        if(!isset($data["worker_money_for_customer"])){
            $data["worker_money_for_customer"] = 0;
        }
        //判断如果级别已存在
        $level_count = $this->getModel()->with([])->where("level","=",data_get($data,"level"))->count();
        if($level_count>0){
            throw new NoticeException("级别提成已存在");
        }
        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create,"添加提成：".data_get($create,"level"),ActivityLog::MODULE_NAME_ROYALTY);
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
        return $this->getModel()->with([])->findOrFail($id);
    }

    /**
     * @param Request $request
     * @param $id
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "level"=>["required","integer"],
            "customer_money"=>["required","numeric","max:99999999.99"],
            "worker_money"=>["required","numeric","max:99999999.99"],
            "worker_money_for_customer"=>["nullable","numeric","max:99999999.99"],
            "status"=>["required","in:0,1"]
        ]);
        if(!isset($data["worker_money_for_customer"])){
            $data["worker_money_for_customer"] = 0;
        }
        //判断如果级别已存在
        $level_count = $this->getModel()->with([])
                        ->where("level","=",data_get($data,"level"))
                        ->where("id","<>",$id)
                        ->count();
        if($level_count>0){
            throw new NoticeException("级别提成已存在");
        }
        $find = $this->getModel()->with([])->find($id);

        if($find){
            $old = clone $find;
            if($find->update($data)){
                log_action($find,"编辑提成：".data_get($find,"level_text"),ActivityLog::MODULE_NAME_ROYALTY,$old);
                return SimpleResponse::success("编辑成功");
            }
        }

        return SimpleResponse::error("编辑失败");
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
                log_action($find,"设置提成状态：".data_get($find,"status_text"),ActivityLog::MODULE_NAME_WORKER,$old);
                return SimpleResponse::success("设置成功");
            }
            return SimpleResponse::error("设置失败");
        }


        return SimpleResponse::error("设置失败");
    }

}

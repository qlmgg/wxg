<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Level;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LevelController extends SimpleController
{
    //
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new Level();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        return $this->query($request->input());
    }

    public function query($query)
    {
        $model = $this->getModel()->with([]);
        return $model;
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "name"=>["required","string","max:255"],
            "status"=>["required","in:0,1"]
        ]);
        if(empty($data["name"])){
            throw new NoticeException("级别名字不能为空");
        }
        //判断名字是否重复
        $model = $this->getModel();
        $count = $model->where("name","=",$data["name"])->count();
        if($count>0){
            throw new NoticeException("级别已存在");
        }
        $create = $this->getModel()->with([])->create($data);
        log_action($create, "新增级别：" . data_get($create, "name"), ActivityLog::MODULE_NAME_LEVEL);
        return $create;
    }

    public function show(Request $request,$id)
    {
        return $this->getModel()->findOrFail($id);
    }

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
           "name"=>["required","string","max:255"],
           "status"=>["required","in:0,1"]
        ]);
        //判断是否存在其他名称相同得级别
        $count = $this->getModel()->with([])
            ->where("name","=",$data["name"])
            ->where("id","<>",$id)->count();
        if($count){
            throw new NoticeException("级别名称已存在");
        }
        $find = $this->getModel()->with([])->find($id);
        $old = clone $find;
        if(!$find){
            throw new NoticeException("级别不存在");
        }
        $find->update($data);
        log_action($find, "更新级别：" . data_get($find, "name"), ActivityLog::MODULE_NAME_LEVEL,$old);
        return $find;
    }

    public function destroy(Request $request,$id)
    {
        $find = $this->getModel()->findOrFail($id);
        $old = clone $find;
        $find->delete();
        log_action($find, "删除级别：" . data_get($find, "name"), ActivityLog::MODULE_NAME_LEVEL,$old);
        return SimpleResponse::success("删除成功");
    }

    public function levelOptions(Request $request)
    {
        return $this->getModel()->with([])->where("status","=",1)->get(["id as value","name as text"]);
    }

}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use App\Models\FixedCheckItems;
use Illuminate\Http\Request;

class FixedCheckItemsController extends SimpleController
{
    //
    protected function getModel()
    {
        return new FixedCheckItems();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel()->with([]);

        //根据名称搜索
        if($title = data_get($data,"title")){
            $model->where("title","like","%{$title}%");
        }

        return $model;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $data = $this->validate($request, [
            "title" => ["required", "string", "max:255"],
            "status" => ["required", "in:0,1"]
        ]);
        
        $create = $this->getModel()->with([])->create($data);
        if ($create) {
            log_action($create, "固定检查项添加：" . data_get($create, "title"), "固定检查项");
            return SimpleResponse::success("添加成功");
        }

        return SimpleResponse::error("添加失败");
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->getModel()->with([])->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        //
        $data = $this->validate($request, [
            "title" => ["required", "string", "max:255"],
            "status" => ["required", "in:0,1"]
        ]);
        
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find->update($data)){
            log_action($find,"固定检查项编辑：".data_get($find,"title"),"固定检查项",$old);
            return SimpleResponse::success("编辑成功");
        }

        return SimpleResponse::error("编辑失败");
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function status($id, Request $request)
    {
        $data = $this->validate($request,[
           "status" => ["required","in:0,1"]
        ]);

        $find = $this->getModel()->with([])->find($id);

        if(!$find) return SimpleResponse::error("无效更改");
        $old = clone $find;
        $find->status = data_get($data,"status");

        if ($find->save()) {
            log_action($find, "固定检查项设置状态：" . data_get($find, "title"), "固定检查项", $old);
            return SimpleResponse::success("操作成功");
        }
        return SimpleResponse::error("操作失败");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find && $find->delete()){
            log_action($find,"固定检查项删除：".data_get($find,"title"),"固定检查项",$old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\SimpleResponse;
use App\Models\Brands;
use Illuminate\Http\Request;

class BrandsController extends SimpleController
{
    protected function getModel()
    {
        return new Brands();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with([]);

        // 名称搜索
        if ($name = data_get($data, "name")) $model->where("name", "like", "%{$name}%");

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
            "name" => ["required", "string", "max:255"],
            "status" => ["required", "in:0,1"]
        ]);

        $info = $this->getModel()->with([])
            ->where("name", "=", data_get($data, "name"))
            ->count("id");
        if ($info) return SimpleResponse::error("请勿重复添加");

        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create,"品牌管理 添加：".data_get($create,"name"),"品牌管理");
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
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $data = $this->validate($request, [
            "name" => ["required", "string", "max:255"],
            "status" => ["required", "in:0,1"]
        ]);
        
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find->update($data)){
            log_action($find,"品牌管理 编辑：".data_get($find,"name"),"品牌管理",$old);
            return SimpleResponse::success("编辑成功");
        }

        return SimpleResponse::error("编辑失败");
    }


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
            log_action($find, "品牌管理 设置状态：" . data_get($find, "name"), "品牌管理", $old);
            return SimpleResponse::success("操作成功");
        }
        return SimpleResponse::error("操作失败");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Brands  $brands
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find && $find->delete()){
            log_action($find,"品牌管理 删除：".data_get($find,"name"),"品牌管理",$old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }
}

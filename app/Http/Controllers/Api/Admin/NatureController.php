<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nature;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;

class NatureController extends Controller
{

    /**
     * 列表
     *
     * @param  \App\Models\Nature  $table
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Nature $table)
    {
        $per_page = $request->input("per_page", 15);
        $status = $request->input("status", -1);

        $model = $table->with([]);

        if (-1 < $status) {
            $model->where("status", "=", $status);
        }

        $list = $model->simplePaginate($per_page);
        return SimpleResponse::success("请求成功", $list);
    }
    
    /**
     * 详情
     *
     * @param  \App\Models\Nature  $table
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function info(Request $request, Nature $table)
    {
        $id = $request->input("id");
        $find = $table->with([])->find($id);
        return SimpleResponse::success("请求成功", $find);
    }

    /**
     * 添加
     *
     * @param  \App\Models\Nature  $table
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request, Nature $table)
    {
        $data = $this->validate($request, [
            'name' => ["required"],
            'status' => ["required"]
        ]);

        $data["created_at"] = $data["updated_at"] = date("Y-m-d H:i:s");

        $create = $table->with([])->create($data);

        if ($create) {
            log_action($create, "添加建筑性质：".data_get($create, "name"), "建筑性质");
            return SimpleResponse::success("操作成功");
        } else {
            return SimpleResponse::error("网络错误");
        }
    }
    
    /**
     * 编辑
     *
     * @param  \App\Models\Nature  $table
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Nature $table)
    {
        $data = $this->validate($request, [
            'id' => ["required"],
            'name' => ["required"],
            'status' => ["required"]
        ]);

        $data["updated_at"] = date("Y-m-d H:i:s");

        $find = $table->with([])->find(data_get($data, 'id'));
        if ($find) {
            $old = clone $find;
        }

        if ($find->update($data)) {
            log_action($find, "编辑建筑性质：".data_get($find, "name"), "建筑性质", $old);
            return SimpleResponse::success("操作成功");
        } else {
            return SimpleResponse::error("网络错误");
        }
    }
    
    /**
     * 状态
     *
     * @param  \App\Models\Nature  $table
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request, Nature $table)
    {
        $data = $this->validate($request, [
            'id' => ["required"],
            'status' => ["required"]
        ]);

        $id = $data["id"];
        unset($data["id"]);
        $data["updated_at"] = date("Y-m-d H:i:s");

        $find = $table->with([])->find($id);
        if ($find) {
            $old = clone $find;
        }

        if ($find->update($data)) {
            log_action($find, "建筑性质状态：".data_get($find, "name"), "建筑性质", $old);
            return SimpleResponse::success("操作成功");
        } else {
            return SimpleResponse::error("网络错误");
        }
    }
    
    /**
     * 状态
     *
     * @param  \App\Models\Nature  $table
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function del(Request $request, Nature $table)
    {
        $id = $request->input('id');
        $info = $table->with([])->find($id);
        if($info){
            $old = clone $info;
        }
        if ($info && $info->delete()) {
            log_action($info, "建筑性质删除：".data_get($info, "name"), "建筑性质", $old);
            return SimpleResponse::success("删除成功");
        } else {
            return SimpleResponse::error("删除失败");
        }
    }
}

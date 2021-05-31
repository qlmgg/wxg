<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Region;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class RegionController extends SimpleController
{
    /*
     * 获取模型
     */
    public function getModel()
    {
        return new Region();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {
       return $this->getModel()->with([]);
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
            "province_code"=>["required","string","max:255"],
            "city_code"=>["required","string","max:255"],
            "district_code"=>["required","string","max:255"],
            "status"=>["required","integer","in:0,1"]
        ]);

        //查询名字是否已被占用
        $count = $this->getModel()->with([])->where("name","=",data_get($data,"name"))->count();
        if($count){
            throw new NoticeException("区域名称已存在");
        }

        $area = new Area();
        $province_text = $area->where("code","=",data_get($data,"province_code"))->value("text");
        $city_text = $area->where("code","=",data_get($data,"city_code"))->value("text");
        $district_text = $area->where("code","=",data_get($data,"district_code"))->value("text");

        $data['province_text'] = $province_text;
        $data['city_text'] = $city_text;
        $data['district_text'] = $district_text;

        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create,"添加区域：".data_get($create,"name"),ActivityLog::MODULE_NAME_REGION);
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

    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "name"=>["required","string","max:255"],
            "province_code"=>["required","string","max:255"],
            "city_code"=>["required","string","max:255"],
            "district_code"=>["required","string","max:255"],
            "status"=>["required","integer","in:0,1"]
        ]);

        //查询名字是否已被占用
        $count = $this->getModel()->with([])
            ->where("name","=",data_get($data,"name"))
            ->where("id","<>",$id)->count();
        if($count){
            throw new NoticeException("区域名称已存在");
        }

        $area = new Area();
        $province_text = $area->where("code","=",data_get($data,"province_code"))->value("text");
        $city_text = $area->where("code","=",data_get($data,"city_code"))->value("text");
        $district_text = $area->where("code","=",data_get($data,"district_code"))->value("text");

        $data['province_text'] = $province_text;
        $data['city_text'] = $city_text;
        $data['district_text'] = $district_text;

        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find->update($data)){
            log_action($find,"编辑区域：".data_get($find,"name"),ActivityLog::MODULE_NAME_REGION,$old);
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
            log_action($find,"删除区域：".data_get($find,"name"),ActivityLog::MODULE_NAME_REGION,$old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function option(Request $request)
    {
        $model = $this->getModel()->with([]);
        if ($name = $request->input("text")) {
            $model->where("name", "like", "%{$name}%");
        }
        //return $model->where("status","=",1)->get(["id as value", "name as text"]);
        $options = $model->where("status","=",1)->get();
        //$options = $options->pluck("id","region_text");
        $new = [];
        foreach ($options as $k=>$o){
            $new[] = ["value"=>$o->id,"text"=>$o->region_text];
        }
        return $new;
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use App\Imports\GoodsImport;
use App\Models\Goods;
use App\Models\GoodSku;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class GoodsController extends SimpleController
{
    protected function getModel()
    {
        return new Goods();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with(["brand", "goodSku"]);

        // 名称搜索
        if ($title = data_get($data, "title")) $model->where("title", "like", "%{$title}%");
        // 品牌搜索
        if ($brand_id = data_get($data, "brand_id")) $model->where("brand_id", "=", $brand_id);

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
            "brand_id" => ["required", "integer"],
            "title" => ["required", "string", "max:255"],
            "price" => ["nullable", "numeric"],
            "unit" => ["required", "string"],
            "status" => ["required", "in:0,1"],
            "remark" => ["nullable", "string"],
            "good_sku" => ["array", "nullable"],
            "good_sku.*.name" => ["required", "string", "max:255"],
            "good_sku.*.price" => ["nullable", "numeric"],
        ]);

        return DB::transaction(
            function () use($data) {

                $data["price"] = data_get($data, "price", 0);
                $create = $this->getModel()->with([])->create($data);

                $good_sku = collect(data_get($data, "good_sku"));
                $good_sku->each(
                    function($item) use($create) {
                        $goodSku["goods_id"] = data_get($create, "id", $create);
                        $goodSku["name"] = data_get($item, "name");
                        $goodSku["price"] = data_get($item, "price");

                        $addGoodSku = GoodSku::with([])->create($goodSku);
                        log_action($addGoodSku,"商品管理-商品规格 添加：".data_get($addGoodSku,"name"),"商品管理-商品规格");
                    }
                );

                log_action($create,"商品管理 添加：".data_get($create,"title"),"商品管理");
                return SimpleResponse::success("添加成功");
            }
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        return $this->getModel()->with(["brand", "goodSku"])->findOrFail($id);
    }

    public function skuDel($id)
    {
        $find = GoodSku::with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find && $find->delete()){
            log_action($find,"商品管理-商品规格 删除：".data_get($find,"name"),"商品管理-商品规格",$old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
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
            "brand_id" => ["required", "integer"],
            "title" => ["required", "string", "max:255"],
            "price" => ["nullable", "numeric"],
            "unit" => ["required", "string"],
            "status" => ["required", "in:0,1"],
            "remark" => ["nullable", "string"],
            "good_sku" => ["array", "nullable"],
            "good_sku.*.id" => ["nullable", "integer"],
            "good_sku.*.name" => ["required", "string", "max:255"],
            "good_sku.*.price" => ["nullable", "numeric"],
        ]);

        return DB::transaction(
            function () use($data, $id) {

                $find = $this->getModel()->with([])->find($id);
                if($find){
                    $old = clone $find;
                } else {
                    return SimpleResponse::error("数据异常");
                }

                $data["price"] = data_get($data, "price", 0);
                $find->update($data);

                $good_sku = collect(data_get($data, "good_sku"));
                $good_sku->each(
                    function($item) use($find) {
                        $isExist = GoodSku::with([])->find(data_get($item, "id"));
                        if (!$isExist) {
                            $goodSku["goods_id"] = data_get($find, "id", $find);
                            $goodSku["name"] = data_get($item, "name");
                            $goodSku["price"] = data_get($item, "price");

                            $addGoodSku = GoodSku::with([])->create($goodSku);
                            log_action($addGoodSku,"商品管理-商品规格 添加：".data_get($addGoodSku,"name"),"商品管理-商品规格");
                        } else {
                            $GoodSkusOld = clone $isExist;

                            $goodSku["name"] = data_get($item, "name");
                            $goodSku["price"] = data_get($item, "price");

                            $isExist->update($goodSku);
                            log_action($isExist,"商品管理-商品规格 编辑：".data_get($isExist,"name"),"商品管理-商品规格",$GoodSkusOld);
                        }
                    }
                );

                log_action($find,"商品管理 编辑：".data_get($find,"title"),"商品管理",$old);
                return SimpleResponse::success("编辑成功");
            }
        );
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
            log_action($find, "商品管理 设置状态：" . data_get($find, "title"), "商品管理", $old);
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
        //
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find && $find->delete()){
            log_action($find,"商品管理 删除：".data_get($find,"title"),"商品管理",$old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }

    public function import(Request $request)
    {
        if (!($request->hasFile('file') && $request->file('file')->isValid())) {
            return SimpleResponse::error("文件不存在");
        } else {
            $file = $request->file("file");

            return DB::transaction(
                function () use($file) {
                    try {
                        $goodsImport = new GoodsImport();
                        Excel::import($goodsImport, $file);
                        return SimpleResponse::success($goodsImport->out());
                    } catch (\Exception $e) {
                        return SimpleResponse::error($e->getMessage());
                    }
                }
            );
        }
    }
}

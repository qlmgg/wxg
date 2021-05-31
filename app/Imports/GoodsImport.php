<?php

namespace App\Imports;

use App\Models\Goods;
use App\Models\Brands;
use App\Models\GoodSku;
use Illuminate\Support\Collection;
use App\Exceptions\NoticeException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class GoodsImport implements ToCollection
{
    protected $result;

    public function __construct()
    {
        $this->result = "导入成功";
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $val) {
            if (0 < $key) {
                if (is_numeric($val[4])) {
                    // 获取品牌ID
                    $brand = Brands::with([])->where("name", "=", $val[1])->first();
                    if (!$brand) {
                        $brandInfo["name"] = $val[1];
                        $brandInfo["logo"] = "";
                        $brandInfo["status"] = 1;
                        $brand = Brands::with([])->create($brandInfo);
                    }
    
                    // 查询商品
                    $good = Goods::with([])
                        ->where("brand_id", "=", data_get($brand, "id", $brand))
                        ->where("title", "=", $val[2])
                        ->first();
                    
                    // 是否存在
                    if (!$good) {
                        $good = Goods::with([])->create([
                            'brand_id' => data_get($brand, 'id', $brand),
                            'title' => $val[2],
                            'unit' => $val[5]?$val[5]:"",
                            'remark' => $val[6]?$val[6]:"",
                            "status" => 1
                        ]);
                    }
    
                    // 查询规格
                    $sku = GoodSku::with([])
                        ->where("goods_id", "=", data_get($good, "id", $good))
                        ->where("name", "=", $val[3])
                        ->where("price", "=", $val[4])
                        ->first();
                    
                    // 是否存在
                    if (!$sku) {
                        GoodSku::with([])->create([
                            "goods_id" => data_get($good, "id", $good),
                            "name" => $val[3],
                            "price" => $val[4]
                        ]);
                    }
                } else {
                    throw new NoticeException("序号为 [{$key}] 行 '价格' 数据格式不正确，请检查!");
                    return false;
                }
            }
        }
    }

    public function out()
    {
        return $this->result;
    }
    
}

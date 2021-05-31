<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\Brands;
use App\Models\CheckOrder;
use App\Models\Goods;
use App\Models\GoodSku;
use App\Models\MaterialListRecord;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\SimpleResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialListRecordController extends SimpleController
{
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new MaterialListRecord();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $model = $this->getModel()->with([]);

        $model = $model->where("worker_id","=",$user->id)
            ->where("month_check_worker_id",data_get($query,"month_check_worker_id"));
        return $model;
    }

    public function getMaterialList(Request $request)
    {
        $user = $request->user();
        $month_check_worker = MonthCheckWorker::with([])->find(data_get($request,"month_check_worker_id"));
        if(!$month_check_worker) throw new NoticeException("月检工人信息不存在");
        $model = $this->getModel();
        $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);
        if($check_order->type==1){
            //共享查看 就是月检下的所有材料
            $model = $model->with([])->where("month_check_id","=",$month_check_worker->month_check_id);
        }else{
            $model = $model->with([])->where("worker_id","=",$user->id)
                ->where("month_check_worker_id",data_get($request,"month_check_worker_id"));
        }
        $page_data["gift_num"] = $check_order->gift_num;
        $page_data["gift_time"] = $check_order->gift_time;
        if ($request->header('simple-page') == 'true') {
            $page_data = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $page_data = $model->paginate($request->input("per-page", 15));
        }
        $page_data = collect($page_data)->put("gift_num",$check_order->gift_num);
        $page_data = collect($page_data)->put("gift_time",Carbon::parse($check_order->gift_time)->format("Y-m-d H:i"));
        return $page_data;
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "month_check_worker_id"=>["required","integer"],
            "gift_time"=>["nullable","date_format:Y-m-d H:i"],
            "gift_num"=>["nullable","integer"],
            "goods"=>["nullable","array"],
            "goods.*.id"=>["nullable","integer"],    //更新时传递
            "goods.*.goods_id"=>["required_with:goods","integer"],
            "goods.*.name"=>["required_with:goods","string","max:255"],
            "goods.*.sku"=>["required_with:goods","string","max:255"],
            "goods.*.good_sku_id"=>["required_with:goods","integer"],
            "goods.*.price"=>["required_with:goods","numeric","max:999999.99"],
            "goods.*.num"=>["required_with:goods","integer"],
            "goods.*.type"=>["required_with:goods","in:1,2"],
        ]);
        $user = $request->user();
        //获取月检员工信息表 免费的是共享
        //工人信息
        $month_check_worker = MonthCheckWorker::with([])->where("worker_id","=",$user->id)->where("id","=",data_get($data,"month_check_worker_id"))->first();
        if(!$month_check_worker)throw new NoticeException("信息不存在");
        $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);
        return DB::transaction(function()use($user,$data,$month_check_worker,$check_order){
            $gift_time = data_get($data,"gift_time");
            $gift_num = data_get($data,"gift_num");
            if(is_null($gift_num)) $gift_num=0;
            $goods = data_get($data,"goods");
            $model = $this->getModel()->with([]);
            $goods = collect($goods)->map(function ($item) use($model,$user,$month_check_worker,$check_order,$gift_time,$gift_num){
                $item["check_order_id"] = $month_check_worker->check_order_id;
                $item["month_check_id"] = $month_check_worker->month_check_id;
                $item["month_check_worker_id"] = $month_check_worker->id;
                $item["worker_id"] = $month_check_worker->worker_id;
                $item["total_price"] = round($item["price"]*$item["num"],2);
                $item["gift_time"] = $gift_time;
                $item["gift_num"] = $gift_num;
                $item["type"] = $check_order->type;
                if($id = data_get($item,"id")){

                    $find = $this->getModel()->find($id);
                    //dump($find);
                    if($find){
                        $find->update($item);
                    }else{
                        $create = $this->getModel()->create($item);
                        $item["id"] = $create->id;
                    }
                }else{
                    $create = $this->getModel()->create($item);
                    $item["id"] = $create->id;
                }
                return $item;
            });
            $ids = $goods->pluck("id");
            //dd($ids);
            if(!empty($ids)){
                //$model->where("worker_id","=",$user->id)
                //删除该月检下不存在的所有赠送材料  材料共享
                if($check_order->type==1){
                    $this->getModel()->where("month_check_id","=",$month_check_worker->month_check_id)
                        ->whereNotIn("id",$ids)->delete();
                }else{
                    //如果类型为付费月检则删除自己下面的订单
                    $this->getModel()->where("month_check_id","=",$month_check_worker->month_check_id)
                        ->where("month_check_worker_id","=",$month_check_worker->id)
                        ->where("worker_id","=",$month_check_worker->worker_id)
                        ->whereNotIn("id",$ids)->delete();
                }

            }
            //更新合同的赠送次数
            if($check_order->type==1){
                $check_order->gift_num = $gift_num;
                $check_order->gift_time = $gift_time;
                $check_order->is_gift  = 1;
                $check_order->save();
            }
            return SimpleResponse::success("保存成功");
        });

    }

    public function chooseGoods(Request $request)
    {
        $model = new Goods();
        $model = $model->with(['brand'])->where("status","=",1);
        //更根据品牌和商品名称搜索
        if($brand_id=data_get($request,"brand_id")){
            if(!empty($brand_id)){
                $model = $model->where("brand_id","=",$brand_id);
            }

        }

        if($name=data_get($request,"name")){
            if(!empty($name)){
                $model = $model->where("title","like","%{$name}%");
            }

        }

        if ($request->header('simple-page') == 'true') {
            return $model->simplePaginate($request->input("per-page", 15));
        } else {
            return $model->paginate($request->input("per-page", 15));
        }
    }

    public function chooseGoodsSku(Request $request,$goods_id)
    {
        $model = new GoodSku();
        return $model->with([])->where("goods_id","=",$goods_id)->get();
    }

    public function brandOptions(Request $request)
    {
        $model = new Brands();
        $model = $model->with([]);
        if ($name = $request->input("text")) {
            $model->where("name", "like", "%{$name}%");
        }

        return $model->where("status","=",1)->get(["id as value", "name as text"]);
    }

}

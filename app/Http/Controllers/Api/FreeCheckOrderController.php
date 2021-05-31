<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\CheckOrder;
use App\Models\CheckOrderComments;
use App\Models\Contracts;
use App\Models\FaultSummaryRecord;
use App\Models\MaterialListRecord;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\SimpleResponse;
use App\Models\SiteConditions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FreeCheckOrderController extends SimpleController
{
    //
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new CheckOrder();
    }
    public function  search(Request $request): Builder
    {
        // TODO: Implement search() method.
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $model = $this->getModel()->with(["monthCheckWorker.worker.royalty"]);
        $model = $model->where("user_id","=",$user->id)->where("type","=",1);
        return $model;
    }

    public function show($id)
    {
        //return $this->getModel()->with(['monthCheck.workers','monthCheck.siteConditions',"monthCheck.faultSummaryRecord","monthCheck.materialListRecord","monthCheck.checkOrderComments"])->findOrFail($id);
        return $this->getModel()->with(['monthCheck.workers.worker',"region","nature"])->findOrFail($id);
    }

    /**
     * 获取check_order下的所有故障汇总
     * @param $id 检查订单ID
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getOrderFaults($id){
        $model = new FaultSummaryRecord();

         return $model->with(['files.bigFile'])->where("check_order_id","=",$id)->get();
    }

    /**
     * 获取现场情况信息
     * @param $id 检查订单ID
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getSiteConditions($id)
    {
        $model = new SiteConditions();
        return $model->with(['files.bigFile'])->where("check_order_id","=",$id)->get();
    }

    /**
     * 获取合同情况
     * @param $id 检查订单ID
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getContracts($id)
    {
        $model = new Contracts();
        return $model->with("files.bigFile")->where("check_order_id","=",$id)->get();
    }

    public function getMaterials($id)
    {
        $check_order = CheckOrder::with([])->find($id);
        $data["gift_num"] = $check_order->gift_num;
        $model = new MaterialListRecord();
        $materials = $model->with(["goods.brand"])->where("check_order_id","=",$id)->get();
        $data["materials"] = $materials;
        $data["total_money"] = $model->with([])->where("check_order_id","=",$id)->sum("total_price");
        return $data;
    }

    public function comment(Request $request)
    {
        $data = $this->validate($request,[
            "check_order_id"=>["required","integer"],
            "content"=>["required","string","max:255"],
            "files"=>["nullable","array"],
            "files.*.id"=>["nullable","integer"],
            "files.*.big_file_id"=>["required_with:files","integer"],
            "files.*.name"=>["required_with:files","string","max:255"]
        ]);
        $user = $request->user();
        return DB::transaction(function ()use($data,$user){
            $data["user_id"] = $user->id;
            $comment_model = new CheckOrderComments();
            $comment = $comment_model->with([])->create($data);
            $files = data_get($data,"files");
            if(!empty($files)){
                $files = collect($files)->map(function($item)use($comment){
                    $item['check_order_comments_id'] = $comment->id;
                    //获取文件信息
                    $file = $this->getFile($item["big_file_id"]);
                    $item['url'] = $file->url;
                    return $item;
                });
                $comment->syncFiles($files->toArray());
            }
            return SimpleResponse::success("评价成功");

        });
    }

    /**
     * 根据ID获取文件信息
     * @param Request $request
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }

    public function getComments($check_order_id)
    {
        $comment_model = new CheckOrderComments();
         return $comment_model->with(["files.bigFile"])->where("check_order_id","=",$check_order_id)->get();
    }

    public function getWorkerActionInfo(Request $request)
    {

        $data = $this->validate($request, [
            "check_order_id" => ["required","integer"],
            "worker_id" => ["required","integer"]
        ]);

        return MonthCheckWorker::with([
            "checkOrder.region",
            "checkOrder.nature",
            "monthCheck",
            "worker",
            "monthCheckWorkerAction.files",
        ])
            ->where("check_order_id", "=", data_get($data, "check_order_id"))
            ->where("worker_id", "=", data_get($data, "worker_id"))
            ->first();
    }

}

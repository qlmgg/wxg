<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\CheckOrder;
use App\Models\FaultSummaryRecord;
use App\Models\JobContent;
use App\Models\MonthCheckWorker;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaultSummaryRecordController extends SimpleController
{
    //
    public function getModel()
    {
        return new FaultSummaryRecord();
    }

    public function search(Request $request): Builder
    {
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $month_check_worker_id = data_get($query,"month_check_worker_id");
        $model = $this->getModel()->with(['files.bigFile']);
        //如果是免费订单 则信息共享
        $month_check_worker = MonthCheckWorker::with([])->find($month_check_worker_id);
        $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);

        if($check_order->type==1){
            //共享查看 就是月检下的所有材料
            $model = $model->with([])->where("month_check_id","=",$month_check_worker->month_check_id);
        }else{
            $model = $model->with([])->where("worker_id","=",$user->id)
                ->where("month_check_worker_id",$month_check_worker_id);
        }

        //$model = $model->where("month_check_worker_id","=",$month_check_worker_id)->where("worker_id","=",$user->id);
        return $model;
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "month_check_worker_id"=>["required","integer"],
            "faults"=>["required","array"],
            "faults.*.id"=>["nullable","integer"],//更新时传递
            "faults.*.title"=>["required","string","max:255"],
            "faults.*.status"=>["required","in:0,1"],
            "faults.*.files"=>["nullable","array"],
            "faults.*.files.*.big_file_id"=>["required","integer"],
            "faults.*.files.*.name"=>["required","string","max:255"],
            "faults.*.files.*.id"=>["nullable","integer"] //更新时传递
        ]);
        $user = $request->user();
        return DB::transaction(function ()use($data,$user){

            $month_check_worker = MonthCheckWorker::with([])->findOrFail(data_get($data,"month_check_worker_id"));
            if(empty($month_check_worker->toArray())) throw new NoticeException("月检不存在");
            $faults = data_get($data,"faults");
            $faults = collect($faults)->map(function($fault)use($month_check_worker,$user){
                $fault['check_order_id'] = $month_check_worker->check_order_id;
                $fault['month_check_id'] = $month_check_worker->month_check_id;
                $fault['month_check_worker_id'] = $month_check_worker->id;
                $fault['worker_id'] = $user->id;

                $model = $this->getModel()->with([]);
                if($id = data_get($fault,"id")){
                    $f = $model->find($id);
                    if($f){
                        $f->update($fault);
                    }else{
                        $f = $model->create($fault);
                        $fault["id"] = $f->id;
                    }

                }else{
                    $f = $model->create($fault);
                    $fault["id"] = $f->id;
                }

                //同步故障汇总文件
                $files = data_get($fault,"files");
                $files = collect($files)->map(function ($item){
                    $file = $this->getFile($item['big_file_id']);
                    $item['url'] = $file->url;
                    return $item;
                });
                $f->syncFiles($files->toArray());
                return $fault;

            });
            $ids = $faults->pluck("id");
            //如果订单类型是1则是共享类型 则需删除月检下所有不存在的信息
            $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);
            if($check_order->type==1){
                $this->getModel()->with([])
                    ->where("month_check_id","=",$month_check_worker->month_check_id)
                    ->whereNotIn("id",$ids)->delete();
            }else{
                //查询所有不存在该ID中的记录并删除 对应的时该条月检信息员工表中的数据
                $this->getModel()->with([])
                    ->where("month_check_worker_id","=",data_get($data,"month_check_worker_id"))
                    ->where("worker_id","=",$user->id)
                    ->whereNotIn("id",$ids)->delete();
            }

            return SimpleResponse::success("提交成功");
        });

    }

    /**
     * 根据ID获取文件信息
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }


}

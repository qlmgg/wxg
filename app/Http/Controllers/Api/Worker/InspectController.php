<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\CheckOrder;
use App\Models\MonthCheckWorker;
use App\Models\SimpleResponse;
use App\Models\SiteConditions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class InspectController 月检表-与现场情况用的同一张表（内容相同）
 * @package App\Http\Controllers\Api\Worker
 */
class InspectController extends SimpleController
{
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new SiteConditions();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $month_check_worker_id = data_get($query,"month_check_worker_id");
        $model = $this->getModel()->with(['files.bigFile']);

        //现场情况反馈是免费月检才有  所有员工共享信息 只需通过month_check_id 查询就可以
        $month_check_worker = MonthCheckWorker::with([])->find($month_check_worker_id);
        $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);
        if($check_order->type==1){
            //共享查看 就是月检下的所有材料
            $model = $model->with([])->where("type","=",2)->where("month_check_id","=",$month_check_worker->month_check_id);
        }else{
            $model = $model->with([])->where("type","=",2)->where("worker_id","=",$user->id)->where("month_check_worker_id",$month_check_worker_id);
        }
        //$model = $model->where("month_check_worker_id","=",$month_check_worker_id)->where("worker_id","=",$user->id);
        return $model;
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "month_check_worker_id"=>["required","integer"],
            "siteConditions"=>["required","array"],
            "siteConditions.id"=>["nullable","integer"],//更新时传递
            "siteConditions.remarks"=>["nullable","string","max:255"],
            "siteConditions.files"=>["nullable","array"],
            "siteConditions.files.*.big_file_id"=>["required","integer"],
            "siteConditions.files.*.name"=>["required","string","max:255"],
            "siteConditions.files.*.id"=>["nullable","integer"] //更新时传递
        ]);
        $user = $request->user();
        DB::transaction(function ()use($data,$user){
            $month_check_worker = MonthCheckWorker::with([])->findOrFail(data_get($data,"month_check_worker_id"));
            if(empty($month_check_worker->toArray())) throw new NoticeException("月检不存在");
            $conditions = data_get($data,"siteConditions");
            $conditions['check_order_id'] = $month_check_worker->check_order_id;
            $conditions['month_check_id'] = $month_check_worker->month_check_id;
            $conditions['month_check_worker_id'] = $month_check_worker->id;
            $conditions['worker_id'] = $user->id;
            $conditions['type']=2;
            $model = $this->getModel()->with([]);
            $c = $model->where("month_check_worker_id","=",$month_check_worker->id)
                        ->where("type","=",2)
                        ->where("worker_id","=",$user->id)->first();
            if($c){
                $c->update($conditions);
            }else{
                $c = $model->create($conditions);
            }
            //同步故障汇总文件
            $files = data_get($conditions,"files");
            $files = collect($files)->map(function ($item){
                $file = $this->getFile($item['big_file_id']);
                $item['url'] = $file->url;
                return $item;
            });
            $c->syncFiles($files->toArray());
            $checkCount = $this->getModel()->with([])
                ->where("month_check_id","=",$month_check_worker->month_check_id)
                ->count();
            if ($checkCount > 1) {
                throw new NoticeException("当前月检单已提交");
            }

        });
        return SimpleResponse::success("提交成功");
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

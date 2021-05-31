<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\CheckOrder;
use App\Models\Contracts;
use App\Models\MonthCheckWorker;
use App\Models\SimpleResponse;
use App\Models\SiteConditions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractsController extends SimpleController
{
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new Contracts();
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
        //合同上传是免费月检才有  所有员工共享信息 只需通过month_check_id 查询就可以
        $month_check_worker = MonthCheckWorker::with([])->find($month_check_worker_id);
        $check_order = CheckOrder::with([])->find($month_check_worker->check_order_id);
        if($check_order->type==1){
            //共享查看 就是月检下的所有材料
            $model = $model->with([])->where("month_check_id","=",$month_check_worker->month_check_id);
        }else{
            $model = $model->with([])->where("worker_id","=",$user->id)->where("month_check_worker_id",$month_check_worker_id);
        }

        //$model = $model->where("month_check_worker_id","=",$month_check_worker_id)->where("worker_id","=",$user->id);
        return $model;
    }

    public function store(Request $request)
    {
        $data = $this->validate($request,[
            "month_check_worker_id"=>["required","integer"],
            "contract"=>["required","array"],
            "contract.remarks"=>["nullable","string","max:255"],
            "contract.files"=>["required","array"],
            "contract.files.*.id"=>["nullable","integer"],
            "contract.files.*.big_file_id"=>["required","integer"],
            "contract.files.*.name"=>["required","string","max:255"]
        ]);

        $user = $request->user();
        return DB::transaction(function () use($data,$user){
            $month_check_worker = MonthCheckWorker::with([])->findOrFail(data_get($data,"month_check_worker_id"));
            if(empty($month_check_worker->toArray())) throw new NoticeException("月检不存在");
            $contract = data_get($data,"contract");
            $contract['check_order_id'] = $month_check_worker->check_order_id;
            $contract['month_check_id'] = $month_check_worker->month_check_id;
            $contract['month_check_worker_id'] = $month_check_worker->id;
            $contract['worker_id'] = $user->id;
            $model = $this->getModel()->with([]);
            //$c = $model->where("month_check_worker_id","=",$month_check_worker->id)->where("worker_id","=",$user->id)->first();
            //通过 month_check_id
            $c = $model->where("month_check_id","=",$month_check_worker->month_check_id)->first();
            if($c){
                $c->update($contract);
            }else{
                $c = $model->create($contract);
            }
            //同步故障汇总文件
            $files = data_get($contract,"files");
            $files = collect($files)->map(function ($item){
                $file = $this->getFile($item['big_file_id']);
                $item['url'] = $file->url;
                return $item;
            });
            $c->syncFiles($files->toArray());

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

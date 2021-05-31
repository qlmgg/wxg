<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\DemandChangeStatusEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\ActivityLog;
use App\Models\DemandCommunication;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemandCommunicationController extends SimpleController
{
    //
    public function getModel()
    {
        return new DemandCommunication();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder
    {
        $model = $this->getModel();
        $model = $model->with([]);
        $model = $model->where("demand_id","=",data_get($data,"demand_id"));
        return $model;

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
            "status"=>["required","in:1,-1"],
            "door_at"=>["nullable","date_format:Y-m-d H:i"],
            "content"=>["nullable","string","max:255"]
        ]);

        $find = $this->getModel()->with([])->find($id);
        if(!$find) throw new NoticeException("操作错误");
        return DB::transaction(function () use ($data,$find){
            $old = clone $find;
            $find->update($data);
            $demand = $find->demand()->first();
            event(new DemandChangeStatusEvent($demand,$find->status));
            log_action($find, "编辑需求沟通：" . data_get($find, "id"), ActivityLog::MODULE_NAME_DEMAND,$old);
            return SimpleResponse::success("编辑成功");
        });
    }

    public function destroy($id)
    {
        $find = $this->getModel()->with([])->findOrFail($id);
        $old = clone $find;
        $find->delete();
        log_action($find, "删除需求沟通：" . data_get($find, "id"), ActivityLog::MODULE_NAME_DEMAND,$old);
        return SimpleResponse::success("删除成功");
    }

}

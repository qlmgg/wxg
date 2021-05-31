<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;

class MonthCheckWorkersController extends SimpleController
{
    protected function getModel()
    {
        return new MonthCheckWorker();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with(["worker", "checkOrder"]);
        // 月检合同订单ID
        if ($check_order_id = data_get($data, "check_order_id"))
            $model->where("check_order_id", "=", $check_order_id);

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
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $find = $this->getModel()->with([])->find($id);
        if (1<data_get($find, "status")) throw new NoticeException("异常操作");
        
        if ($find) $old = clone $find;
        
        if ($find && $find->delete()) {
            $monthCheckInfo = MonthCheck::with([])->find(data_get($find, "month_check_id"));
            $left_worker_num = data_get($monthCheckInfo, "left_worker_num") + 1;
            $monthCheckInfo->update(['left_worker_num'=>$left_worker_num]);
            
            log_action($find, "月检合同订单-派单员工 删除：ID " . data_get($find, "id"), "月检合同订单-派单员工", $old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }
}

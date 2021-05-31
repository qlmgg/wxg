<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\SimpleResponse;
use App\Models\CheckOrder;
use App\Models\CommunicationRecord;
use Illuminate\Http\Request;

class CommunicationRecordController extends SimpleController
{
    protected function getModel()
    {
        return new CommunicationRecord();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with([]);

        if ($check_order_id = data_get($data, "check_order_id")) {
            $model->where("check_order_id", "=", $check_order_id);
        } else {
            throw new NoticeException("参数异常");
        }

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
        $data = $this->validate($request, [
            "enterprise_name" => ["required", "string"],
            "building_area" => ["required", "integer"],
            "nature_id" => ["required", "integer"],
            "region_id" => ["required", "integer"],
            "name" => ["required", "string"],
            "mobile" => ["required", "string"],
            "address" => ["required", "string"],
            "communication" => ["required", "array"],
            "communication.check_order_id" => ["required", "integer"],
            "communication.content" => ["required", "string"],
            "communication.estimate_time" => ["nullable", "date_format:Y-m-d H:i:s"],
            "communication.status" => ["required", "in:-1,1"],
        ]);
        $communication = data_get($data, "communication");
        $communication["worker_id"] = data_get($request->user(), "id");
        $create = $this->getModel()->with([])->create($communication);
        if($create){
            $checkOrder = CheckOrder::with([])->find(data_get($create, "check_order_id"));
            switch ($create->status) {
                case 1:
                    $data["customer_status"] = 1;
                    break;
                default:
                    $data["customer_status"] = -1;
                    break;
            }
            // 更新免费检查订单状态
            $checkOrder->update($data);
            // 添加日志记录
            log_action($create, "免费检查订单-操作-沟通添加：ID " . data_get($create, "id"), "免费检查订单-操作-沟通");
            return SimpleResponse::success("添加成功");
        }
        return SimpleResponse::error("添加失败");
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
        return $this->getModel()->with([])->findOrFail($id);
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
        $data = $this->validate($request, [
            "content" => ["required", "string"],
            "estimate_time" => ["nullable", "date_format:Y-m-d H:i:s"],
            "status" => ["required", "in:-1,1"],
        ]);

        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        $data["worker_id"] = data_get($request->user(), "id");
        if($find->update($data)){
            $checkOrder = CheckOrder::with([])->find(data_get($find, "check_order_id"));
            switch ($find->status) {
                case 1:
                    $checkOrder->customer_status = 1;
                    break;
                default:
                    $checkOrder->customer_status = -1;
                    break;
            }
            // 更新免费检查订单状态
            $checkOrder->update();
            log_action($find, "免费检查订单-操作-沟通编辑：ID " . data_get($find, "id"), "免费检查订单-操作-沟通", $old);
            return SimpleResponse::success("编辑成功");
        }
        return SimpleResponse::error("编辑失败");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
        }
        if($find && $find->delete()){
            log_action($find, "免费检查订单-操作-沟通删除：ID " . data_get($find, "id"), "免费检查订单-操作-沟通", $old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }
}

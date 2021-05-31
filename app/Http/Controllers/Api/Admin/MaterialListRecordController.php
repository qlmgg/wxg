<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\MaterialListRecord;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;

class MaterialListRecordController extends SimpleController
{
    protected function getModel()
    {
        return new MaterialListRecord();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with(["checkOrder", "goods.brand"]);

        // 检查订单ID
        if ($check_order_id = data_get($data, "check_order_id")) {
            $model->where("check_order_id", "=", $check_order_id);
        } else {
            $model->where("check_order_id", "=", 0);
        }
        // 月检订单ID
        if ($month_check_id = data_get($data, "month_check_id")) {
            $model->where("month_check_id", "=", $month_check_id);
        }

        return $model;
    }

    public function index()
    {
        $request = request();
        $model = $this->search($request);

        foreach ($this->orderBy as $val) {
            $column = data_get($val, 'column');
            $direction = data_get($val, 'direction');
            if ($column && $direction) {
                $model->orderBy($column, $direction);
            }
        }

        $MaterialListRecord = clone $model;

        $total_price = $MaterialListRecord->sum("total_price");

        if ($request->header('simple-page') == 'true') {
            $list = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $list = $model->paginate($request->input("per-page", 15));
        }

        $data = [
            "total_price" => $total_price,
            "list" => $list
        ];

        return $data;
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
    }
}

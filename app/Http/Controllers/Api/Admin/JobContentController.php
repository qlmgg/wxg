<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\JobContent;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;

class JobContentController extends SimpleController
{
    protected function getModel()
    {
        return new JobContent();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel();
        $model = $model->with(['worker', 'files']);

        // 检查订单ID
        if ($check_order_id = data_get($data, "check_order_id")) {
            $model->where("check_order_id", "=", $check_order_id);
        } else {
            throw new NoticeException("参数异常");
        }
        // 月检订单ID
        if ($month_check_id = data_get($data, "month_check_id")) {
            $model->where("month_check_id", "=", $month_check_id);
        } else {
            throw new NoticeException("month_check_id 不能为空！");
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

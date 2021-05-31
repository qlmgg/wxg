<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\MonthCheckWorkerAction;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;

class EmployeeWorkRecordController extends SimpleController
{
    
    protected $orderBy = [
        [
            'column' => 'id',
            'direction' => 'asc'
        ]
    ];
    
    //
    protected function getModel()
    {
        return new MonthCheckWorkerAction();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel()->with(['files', 'worker']);

        if ($check_order_id = data_get($data, "check_order_id")) {
            $model->where("check_order_id", "=", $check_order_id);
        } else {
             throw new NoticeException("参数异常");
        }

        if ($month_check_id = data_get($data, "month_check_id")) {
            $model->where("month_check_id", "=", $month_check_id);
        } else {
             throw new NoticeException("参数异常");
        }
        

        if ($worker_id = data_get($data, "worker_id")) {
            $model->where("worker_id", "=", $worker_id);
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

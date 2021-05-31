<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\FlowExpensesToExport;
use App\Http\Controllers\Api\SimpleController;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FlowExpensesExport;
use App\Models\SimpleResponse;
use App\Models\FlowExpenses;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FlowExpensesController extends SimpleController
{
    //
    protected function getModel()
    {
        return new FlowExpenses();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input(), $request->user());
    }

    public function query(array $data, $user): Builder {

        $model = $this->getModel()->with(['region', 'checkOrder']);
        // 企业名称搜索
        if ($enterprise_name = data_get($data, "enterprise_name")) {
            $model->whereHas("checkOrder",
                function (Builder $query) use($enterprise_name) {
                    $query->where('enterprise_name', 'like', "%{$enterprise_name}%");
                }
            );
        }
        // 员工名称搜索
        if ($name = data_get($data,"name")) $model->where("name", "like", "%{$name}%");
        // 结算时间范围搜索
        if ($date_range = data_get($data, "date_range")) $model->whereBetween("created_at", explode("~", $date_range));
        //订单类型
        if ($type = data_get($data,"type")){
            $model->whereHas("checkOrder",function ($query) use ($type){
                $query->where("type","=",$type);
            });
        }

        // 如果登录为区域经理，则只展示此区域数据
        if (data_get($user, "type") == 2) $model->where("region_id", "=", data_get($user, "region_id"));

        return $model;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $request = request();
        $model = $this->search($request);
        foreach ($this->orderBy as $val) {
            $column = data_get($val, 'column');
            $direction = data_get($val, 'direction');
            if ($column && $direction) {
                $model->orderBy($column, $direction);
            }
        }

        $FlowExpenses1 = clone $model;
        $FlowExpenses2 = clone $model;
        $FlowExpenses3 = clone $model;
        $FlowExpenses4 = clone $model;
        $FlowExpenses5 = clone $model;

        if ($request->header('simple-page') == 'true') {
            $list = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $list = $model->paginate($request->input("per-page", 15));
        }

        $total_amount = $FlowExpenses1->sum("money");
        $total_profit = $FlowExpenses2->sum("profit");
        $total_service_time = $FlowExpenses3->sum("service_time");
        $total_client_settlement = $FlowExpenses4->whereHas("checkOrder",function ($query){
            $query->where("type","=",2);
        })->sum("client_settlement");
        $total_free_client_settlement = $FlowExpenses5->whereHas("checkOrder",function ($query){
            $query->where("type","=",1);
        })->sum("client_settlement");

        $data["list"] = $list;
        $data["total_amount"] = round($total_amount, 2);
        $data["total_profit"] = round($total_profit, 2);
        $data["total_service_time"] = $total_service_time;
        $data["total_client_settlement"] = round($total_client_settlement, 2);
        $data["total_free_client_settlement"] = round($total_free_client_settlement, 2);

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
     * @param  \App\Models\FlowExpenses  $flowExpenses
     * @return \Illuminate\Http\Response
     */
    public function show(FlowExpenses $flowExpenses)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FlowExpenses  $flowExpenses
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FlowExpenses $flowExpenses)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FlowExpenses  $flowExpenses
     * @return \Illuminate\Http\Response
     */
    public function destroy(FlowExpenses $flowExpenses)
    {
        //
    }

    public function export()
    {
        return Excel::download(new FlowExpensesExport, Carbon::today() . '-FlowExpenses.xlsx');
    }

    /**
     * 导出培训班学员
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function toExport(Request $request)
    {
        //$worker = Worker::with([])->find(1);
        //return (new FlowExpensesToExport($request->all(), $this, $worker))->download("支付流水.xlsx");
        return (new FlowExpensesToExport($request->all(), $this, $request->user()->toArray()))->download("支付流水.xlsx");
    }
}

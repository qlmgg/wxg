<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StreamingRevenueExport;
use App\Exports\StreamingRevenueToExport;
use App\Models\SimpleResponse;
use App\Models\StreamingRevenue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StreamingRevenueController extends SimpleController
{
    //
    protected function getModel()
    {
        return new StreamingRevenue();
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
                function ($query) use ($enterprise_name){
                    $query->where("enterprise_name", "like", "%{$enterprise_name}%");
                }
            );
        }
        // 联系人搜索
        if ($name = data_get($data,"name")) {
            $model->whereHas("checkOrder", 
                function ($query) use ($name){
                    $query->where("name", "like", "%{$name}%");
                }
            );
        }
        // 联系方式搜索
        if ($mobile = data_get($data,"mobile")) {
            $model->whereHas("checkOrder", 
                function ($query) use ($mobile){
                    $query->where("mobile", "like", "%{$mobile}%");
                }
            );
        }
        // 支付方式搜索
        if ($pay_type = data_get($data,"pay_type")) $model->where("pay_type","=",$pay_type);
        // 支付时间范围搜索
        if ($date_range = data_get($data, "date_range")) $model->whereBetween("created_at", explode("~", $date_range));
        // 如果登录为区域经理，则只展示此区域数据
        if (data_get($user, "type") == 2) {
            $model->where("region_id", "=", data_get($user, "region_id"));
        } else {
            // 所属区域搜索
            if ($region_id = data_get($data, "region_id")) $model->where("region_id", "=", $region_id);
        }

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

        $sum_model = clone $model;
        $total_amount = $sum_model->sum("money");

        if ($request->header('simple-page') == 'true') {
            $list = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $list = $model->paginate($request->input("per-page", 15));
        }

        foreach ($list as &$val) {
            $val->name = $val->checkOrder->name;
            $val->mobile = $val->checkOrder->mobile;
            $val->enterprise_name = $val->checkOrder->enterprise_name;
            $val->save();
        }

        $data["list"] = $list;
        $data["total_amount"] = $total_amount;
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
     * @param  \App\Models\StreamingRevenue  $streamingRevenue
     * @return \Illuminate\Http\Response
     */
    public function show(StreamingRevenue $streamingRevenue)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StreamingRevenue  $streamingRevenue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StreamingRevenue $streamingRevenue)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StreamingRevenue  $streamingRevenue
     * @return \Illuminate\Http\Response
     */
    public function destroy(StreamingRevenue $streamingRevenue)
    {
        //
    }

    public function export() 
    {
        return Excel::download(new StreamingRevenueExport, Carbon::today() . '-StreamingRevenue.xlsx');
    }


    /**
     * 导出
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function toExport(Request $request)
    {
        return (new StreamingRevenueToExport($request->all(), $this, $request->user()->toArray()))->download("收入流水.xlsx");
    }
}

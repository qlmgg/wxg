<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\NoticeException;
use App\Models\SimpleResponse;
use App\Models\CheckOrder;
use App\Models\ContractManagement;
use Illuminate\Http\Request;

class ContractManagementController extends SimpleController
{
    protected function getModel()
    {
        return new ContractManagement();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input(), $request->user());
    }

    public function query(array $data, $user): Builder {

        $model = $this->getModel();
        $model = $model->with(["region", "checkOrder"]);
        // 月检合同订单ID
        if ($check_order_id = data_get($data, "check_order_id")) $model->where("check_order_id", "=", $check_order_id);
        // 企业名称搜索
        if ($enterprise_name = data_get($data, "enterprise_name")) $model->where("enterprise_name", "like", "%{$enterprise_name}%");
        // 联系人搜索
        if ($name = data_get($data, "name")) $model->where("name", "like", "%{$name}%");
        // 联系方式搜索
        if ($mobile = data_get($data, "mobile")) $model->where("mobile", "like", "%{$mobile}%");
        // 如果登录为区域经理，则只展示此区域数据
        if (data_get($user, "type") == 2) {
            $model->where("region_id", "=", data_get($user, "region_id"));
        } else {
            // 所属区域搜索
            if ($region_id = data_get($data, "region_id")) $model->where("region_id", "=", $region_id);
        }
        // 状态搜索 1进行中 2即将过期 3已过期
        if (0<($status = data_get($data, "status"))) $model->where("status", "=", $status);
        // 检查时间范围搜索
        if ($date_range = data_get($data, "date_range")) $model->whereBetween("signature_date", explode("~", $date_range));

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

        $ContractManagement1 = clone $model;

        if ($request->header('simple-page') == 'true') {
            $list = $model->simplePaginate($request->input("per-page", 15));
        } else {
            $list = $model->paginate($request->input("per-page", 15));
        }

        $total_amount = $ContractManagement1->sum("money");

        return ["total_amount"=>$total_amount, "list"=>$list];
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
        $data = $this->validate($request, [
            "check_order_id" => ["required", "integer"],
            "money" => ["required", "string"],
            "signature_date" => ["required", "date_format:Y-m-d H:i:s"],
            "age_limit" => ["required", "integer"],
            "end_date" => ["required", "date_format:Y-m-d H:i:s"],
            "contracts_file" => ["required", "string"],
            "remarks" => ["nullable", "string"]
        ]);

        $checkOrderInfo = CheckOrder::with([])->find(data_get($data, "check_order_id"));
        if (!$checkOrderInfo) return SimpleResponse::error("网络错误");
        
        $data["region_id"] = data_get($checkOrderInfo, "region_id");
        $data["enterprise_name"] = data_get($checkOrderInfo, "enterprise_name");
        $data["name"] = data_get($checkOrderInfo, "name");
        $data["mobile"] = data_get($checkOrderInfo, "mobile");
        $data["status"] = 1;

        $create = $this->getModel()->with([])->create($data);
        if($create){
            log_action($create,"合同管理 添加：".data_get($create,"name"),"合同管理");
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
        return false;
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
        return false;
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
        return false;
    }
}

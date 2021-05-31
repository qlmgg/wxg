<?php

namespace App\Exports;

use App\Http\Controllers\Api\Admin\FlowExpensesController;
use App\Models\CheckOrder;
use App\Models\FlowExpenses;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class FlowExpensesToExport extends DefaultValueBinder implements FromQuery, WithMapping, WithHeadings, WithCustomValueBinder
{

    use Exportable;


    protected $query;
    protected $controller;
    protected $user;

    /**
     * CampusExport constructor.
     */
    public function __construct(array $query, FlowExpensesController $controller,$user)
    {
        $this->query = $query;
        $this->controller = $controller;
        $this->user = $user;
    }

    public function query()
    {
        return $this->controller->query($this->query,$this->user);
    }

    public function map($row): array
    {

        /**
         * @var FlowExpenses $row
         */
        $query = $this->query;
        //获取订单信息
        $order = CheckOrder::with([])->find(data_get($row,"check_order_id"));
        // $order = FlowExpenses::with([])->find(data_get($row,"check_order_id"));
        if(empty($order)) return [];
        //获取员工信息
        $worker = Worker::with([])->find(data_get($row,"worker_id"));
        return [
            $order->order_code,
            $order->enterprise_name,
            $order->money,
            $order->region->region_text,
            $order->type_text,
            $worker->name,
            get_hour(data_get($row,"service_time")),
            data_get($row, "money"),
            data_get($row, "client_settlement"),
            data_get($row, "profit"),
            data_get($row, "settlement_time"),
        ];
    }

    public function headings(): array
    {
        return [
            "订单号",
            "企业名称",
            "合同金额",
            "所在区域",
            "订单类型",
            "员工",
            "服务时长（小时）",
            "支付金额",
            "客户结算",
            "利润",
            "结算日期"
        ];
    }


    public function bindValue(Cell $cell, $value)
    {

        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING2);
            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, $value);
    }

}

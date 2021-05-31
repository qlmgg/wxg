<?php

namespace App\Exports;

use App\Http\Controllers\Api\Admin\StreamingRevenueController;
use App\Models\CheckOrder;
use App\Models\StreamingRevenue;
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

class StreamingRevenueToExport extends DefaultValueBinder implements FromQuery, WithMapping, WithHeadings, WithCustomValueBinder
{

    use Exportable;


    protected $query;
    protected $controller;
    protected $user;

    /**
     * CampusExport constructor.
     */
    public function __construct(array $query, StreamingRevenueController $controller,$user)
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
        $query = $this->query;
        //获取订单信息
        $order = CheckOrder::with([])->find(data_get($row,"check_order_id"));
        if(empty($order)) return [];
        return [
            $order->order_code,
            $order->enterprise_name,
            $order->region->region_text,
            $order->name,
            $order->mobile,
            data_get($row, "money"),
            data_get($row, "pay_type_text"),
            data_get($row, "pay_time")
        ];
    }

    public function headings(): array
    {
        return [
            "订单号",
            "企业名称",
            "所在区域",
            "联系人",
            "联系电话",
            "支付金额",
            "支付方式",
            "支付日期"
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckOrder extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "user_id",
        "free_order_id",
        "demand_id",
        "type",
        "order_code",
        "enterprise_name",
        "building_area",
        "nature_id",
        "region_id",
        "name",
        "mobile",
        "long",
        "lat",
        "area",
        "province",
        "city",
        "address",
        "fixed_duty",
        "worker_num",
        "age_limit",
        "free_amount",
        "num_monthly_inspections",
        "money",
        "payment_type",
        "down_payment",
        "gift_num",
        "remaining_service_num",
        "remark",
        "status",
        "customer_status",
        "is_gift",
        "gift_time",
        "door_time",
        "free_checkup_time",
        "pay_status",
        "remaining_amount",
        "is_show_client_settlement"
    ];

    protected $appends = [
        "type_text",
        "fixed_duty_text",
        "payment_type_text",
        "status_text",
        "customer_status_text",
        "is_gift_text",
        "pay_status_text"
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function nature()
    {
        return $this->belongsTo(Nature::class);
    }

    public function getType()
    {
        return collect([
            1 => collect(["text" => "免费检查订单", "value" => 1]),
            2 => collect(["text" => "月检合同订单", "value" => 2])
        ]);
    }

    public function getTypeTextAttribute()
    {
        $str = $this->getAttribute("type");
        if ($this->getType()->offsetExists($str)) {
            return $this->getType()->get($str)->get("text");
        }
        return null;
    }

    public function getFixedDuty()
    {
        return collect([
            0 => collect(["text" => "否", "value" => 0]),
            1 => collect(["text" => "是", "value" => 1])
        ]);
    }

    public function getFixedDutyTextAttribute()
    {
        $str = $this->getAttribute("fixed_duty");
        if ($this->getFixedDuty()->offsetExists($str)) {
            return $this->getFixedDuty()->get($str)->get("text");
        }
        return null;
    }

    public function getPaymentType()
    {
        return collect([
            1 => collect(["text" => "分期付款", "value" => 1]),
            2 => collect(["text" => "先做后款", "value" => 2]),
            3 => collect(["text" => "先款后做", "value" => 3])
        ]);
    }

    public function getPaymentTypeTextAttribute()
    {
        $str = $this->getAttribute("payment_type");
        if ($this->getPaymentType()->offsetExists($str)) {
            return $this->getPaymentType()->get($str)->get("text");
        }
        return null;
    }

    public function getStatus()
    {
        return collect([
            0 => collect(["text" => "待检查", "value" => 0]),
            1 => collect(["text" => "检查中", "value" => 1]),
            2 => collect(["text" => "已检查", "value" => 2])
        ]);
    }

    public function getStatusTextAttribute()
    {
        $str = $this->getAttribute("status");
        if ($this->getStatus()->offsetExists($str)) {
            return $this->getStatus()->get($str)->get("text");
        }
        return null;
    }

    public function getCustomerStatus()
    {
        return collect([
            -1 => collect(["text" => "已作废", "value" => -1]),
            0 => collect(["text" => "未沟通", "value" => 0]),
            1 => collect(["text" => "继续沟通", "value" => 1]),
            2 => collect(["text" => "已完成", "value" => 2])
        ]);
    }

    public function getCustomerStatusTextAttribute()
    {
        $str = $this->getAttribute("customer_status");
        if ($this->getCustomerStatus()->offsetExists($str)) {
            return $this->getCustomerStatus()->get($str)->get("text");
        }
        return null;
    }

    public function getIsGift()
    {
        return collect([
            0 => collect(["text" => "否", "value" => 0]),
            1 => collect(["text" => "是", "value" => 1])
        ]);
    }

    public function getIsGiftTextAttribute()
    {
        $str = $this->getAttribute("is_gift");
        if ($this->getIsGift()->offsetExists($str)) {
            return $this->getIsGift()->get($str)->get("text");
        }
        return null;
    }

    public function getPayStatus()
    {
        return collect([
            0 => collect(["text" => "未支付", "value" => 0]),
            1 => collect(["text" => "已支付", "value" => 1]),
            2 => collect(["text" => "部分支付", "value" => 2])
        ]);
    }

    public function getPayStatusTextAttribute()
    {
        $str = $this->getAttribute("pay_status");
        if ($this->getPayStatus()->offsetExists($str)) {
            return $this->getPayStatus()->get($str)->get("text");
        }
        return null;
    }

    // 支付列表
    public function paymentManagement()
    {
        return $this->hasMany(PaymentManagement::class);
    }

    // 月检记录
    public function monthCheck()
    {
        return $this->hasMany(MonthCheck::class);
    }

    // 施工员工记录
    public function monthCheckWorker()
    {
        return $this->hasMany(MonthCheckWorker::class);
    }

    // 故障汇总记录
    public function faultSummaryRecord()
    {
        return $this->hasMany(FaultSummaryRecord::class);
    }

    // 现场情况
    public function siteConditions()
    {
        return $this->hasOne(SiteConditions::class);
    }

    // 月检表
    public function monthlyChecklist()
    {
        return $this->hasMany(SiteConditions::class);
    }

    // 免费检查订单合同情况
    public function contracts()
    {
        return $this->hasOne(Contracts::class);
    }

    // 用户端月检合同订单合同情况
    public function userContracts()
    {
        return $this->belongsTo(Contracts::class, "free_order_id");
    }

    // 免费检查订单 赠送材料清单记录/月检合同订单 材料清单记录
    public function materialListRecord()
    {
        return $this->hasMany(MaterialListRecord::class);
    }

    // 月检合同订单 赠送材料清单记录
    public function giftMaterialListRecord()
    {
        return $this->belongsTo(MaterialListRecord::class, "free_order_id");
    }

    // 检查订单评论记录
    public function checkOrderComments()
    {
        return $this->hasMany(CheckOrderComments::class);
    }

    // 月检工作内容
    public function jobContent()
    {
        return $this->hasMany(JobContent::class);
    }

    public function contractManagement()
    {
        return $this->hasOne(ContractManagement::class);
    }

    public function monthCheckOrder()
    {
        return $this->hasOne(CheckOrder::class, "free_order_id");
    }

    public function fixedInspectionRecord()
    {
        return $this->hasMany(FixedInspectionRecord::class);
    }
}

<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthCheck extends Model
{
    use HasFactory,SoftDeletes, HasDateTimeFormatter;
    protected $fillable = [
        "check_order_id",
        "type",
        "worker_num",
        "left_worker_num",
        "time_length",
        "door_time",
        "remark",
        "status"
    ];

    protected $appends = ["status_text"];

    public function getStatus()
    {
        return collect([
            0 => collect(["text" => "待检查", "value" => 0]),
            1 => collect(["text" => "检查中", "value" => 1]),
            2 => collect(["text" => "已完成", "value" => 2])
        ]);
    }

    public function getStatusTextAttribute()
    {
        $status = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($status)){
            return $this->getStatus()->get($status)->get("text");
        }
        return null;
    }

    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }

    public function workers()
    {
        return $this->hasMany(MonthCheckWorker::class);
    }

    // 现场情况/月检表
    public function siteConditions()
    {
        return $this->hasOne(SiteConditions::class);
    }

    // 故障汇总记录
    public function faultSummaryRecord()
    {
        return $this->hasMany(FaultSummaryRecord::class);
    }

    // 月检合同订单 材料清单记录
    public function materialListRecord()
    {
        return $this->hasMany(MaterialListRecord::class);
    }

    // 月检工作内容
    public function jobContent()
    {
        return $this->hasMany(JobContent::class);
    }

    // 检查订单评论记录
    public function checkOrderComments()
    {
        return $this->hasMany(CheckOrderComments::class);
    }

}

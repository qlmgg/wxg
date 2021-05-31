<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthCheckWorker extends Model
{
    use HasFactory,SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "check_order_id",
        "month_check_id",
        "worker_id",
        "status",   //0待接单 1待上门 2检查中  3暂停离场 4已完成 -1已拒绝
        "service_time",
        "earnings",
        "client_settlement",
        "profit",
        "type", //1派单 2为抢单
        "accept_at",
        "end_type",
        "reject_reason",
        "start_at",
        "stop_at",
        "is_show_client_settlement"
    ];

    protected $appends = ["status_text", "type_text", "end_type_text"];

    public function getStatus()
    {
        return collect([
            -1 => collect(["text" => "已拒绝", "value" => -1]),
            0 => collect(["text" => "待接单", "value" => 0]),
            1 => collect(["text" => "待上门", "value" => 1]),
            2 => collect(["text" => "检查中", "value" => 2]),
            3 => collect(["text" => "暂停离场", "value" => 3]),
            4 => collect(["text" => "已完成", "value" => 4])

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

    public function getType()
    {
        return collect([
            1 => collect(["text" => "平台派单", "value" => 1]),
            2 => collect(["text" => "工人抢单", "value" => 2])

        ]);
    }

    public function getTypeTextAttribute()
    {
        $str = $this->getAttribute("type");
        if($this->getType()->offsetExists($str)){
            return $this->getType()->get($str)->get("text");
        }
        return null;
    }

    public function getEndType()
    {
        return collect([
            1 => collect(["text" => "正常签退结束", "value" => 1]),
            2 => collect(["text" => "平台异常结束", "value" => 2])
        ]);
    }

    public function getEndTypeTextAttribute()
    {
        $str = $this->getAttribute("status");
        if($this->getEndType()->offsetExists($str)){
            return $this->getEndType()->get($str)->get("text");
        }
        return null;
    }

    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }

    public function monthCheck()
    {
        return $this->belongsTo(MonthCheck::class);
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function monthCheckWorkerAction()
    {
        return $this->hasMany(MonthCheckWorkerAction::class);
    }
}

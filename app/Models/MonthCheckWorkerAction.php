<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class MonthCheckWorkerAction extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes, HasDateTimeFormatter;
    const ADMIN_SEND_TYPE = 1;
    const WORKER_ACCEPT_TYPE = 2;
    const WORKER_REJECT_TYPE = 3;
    const ENTRANCE_SIGN_TYPE =4;
    const WORK_CONTENT_TYPE = 5;
    const STOP_SIGN_TYPE =6;
    const END_SIGN_TYPE = 7;
    const ABNORMAL_SIGN_TYPE = 8;
    protected $fillable = [
        "check_order_id",
        "month_check_worker_id",
        "month_check_id",
        "worker_id",
        "type",
        "action_time",
        "address",
        "long",
        "lat"
    ];

    protected $appends = ["type_text"];

    public function getTypes()
    {
        return collect([
            1 => collect(["text" => "后台派单", "value" => self::ADMIN_SEND_TYPE]),
            2 => collect(["text" => "工人接单", "value" => self::WORKER_ACCEPT_TYPE]),
            3 => collect(["text" => "工人拒绝", "value" => self::WORKER_REJECT_TYPE]),
            4 => collect(["text" => "入场签到", "value" => self::ENTRANCE_SIGN_TYPE]),
            5 => collect(["text" => "工作内容", "value" => self::WORK_CONTENT_TYPE]),
            6 => collect(["text" => "暂停签退", "value" => self::STOP_SIGN_TYPE]),
            7 => collect(["text" => "结束签退", "value" => self::END_SIGN_TYPE]),
            8 => collect(["text" => "异常签退", "value" => self::ABNORMAL_SIGN_TYPE])
        ]);
    }

    public function getTypeTextAttribute()
    {
        $type = $this->getAttribute("type");
        if($this->getTypes()->offsetExists($type)){
            return $this->getTypes()->get($type)->get("text");
        }
        return null;
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }

    public function monthCheck()
    {
        return $this->belongsTo(MonthCheck::class);
    }

    public function monthCheckWorker()
    {
        return $this->belongsTo(MonthCheckWorker::class);
    }

    public function files()
    {
        return $this->hasMany(MonthCheckWorkerActionFile::class);
    }

    public function syncFiles(array $files)
    {
        $this->syncHasMany($files,$this->files());
    }

}

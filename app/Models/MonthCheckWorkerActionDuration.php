<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthCheckWorkerActionDuration extends Model
{
    use HasFactory,SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "month_check_worker_action_id",
        "check_order_id",
        "month_check_id",
        "month_check_worker_id",
        "worker_id",
        "start_at",
        "stop_at",
        "duration",
        "status"
    ];

}

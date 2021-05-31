<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class FlowExpenses extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "region_id",
        "check_order_id",
        "month_check_id",
        "month_check_worker_id",
        "month_check_worker_action_id",
        "worker_id",
        "order_code",
        "name",
        "service_time",
        "money",
        "client_settlement",
        "profit",
        "settlement_time"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }
}

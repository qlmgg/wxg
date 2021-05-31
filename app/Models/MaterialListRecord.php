<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class MaterialListRecord extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;
    protected $fillable = [
        "check_order_id",
        "month_check_id",
        "month_check_worker_id",
        "worker_id",
        "goods_id",
        "name",
        "sku",
        "good_sku_id",
        "price",
        "num",
        "total_price",
        "type",
        "gift_num",
        "gift_time"
    ];

    protected $hidden = [
        'deleted_at'
    ];

    //
    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class);
    }

    public function goodSku()
    {
        return $this->belongsTo(GoodSku::class);
    }
}

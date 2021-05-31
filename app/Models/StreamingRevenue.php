<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class StreamingRevenue extends Model
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
        "order_code",
        "enterprise_name",
        "name",
        "mobile",
        "money",
        "pay_type",
        "pay_time"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = ["pay_type_text"];

    public function getPayType()
    {
        return collect([
            1=>collect(["text"=>"微信支付","value"=>1]),
            2=>collect(["text"=>"对公账户","value"=>2])
        ]);
    }

    /**
     * @return |null
     */
    public function getPayTypeTextAttribute()
    {
        $str = $this->getAttribute("pay_type");
        if($this->getPayType()->offsetExists($str)){
            return $this->getPayType()->get($str)->get("text");
        }
        return null;
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }
}

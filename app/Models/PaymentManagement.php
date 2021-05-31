<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PaymentManagement extends Model
{
    use HasFactory, SoftDeletes,HasDateTimeFormatter;
    
    protected $fillable = [
        "user_id",
        "check_order_id",
        "payment_order",
        "money",
        "payment_type",
        "date_payable",
        "status",
        "pay_date",
        "pay_type",
        "worker_id",
        "is_process"
    ];
    
    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = [
        "status_text",
        "pay_type_text",
        "payment_type_text"
    ];

    public function getStatus()
    {
        return collect([
            0 => collect(['text'=>'未支付', 'value'=>0]),
            1 => collect(['text'=>'已支付,待确认', 'value'=>1]),
            2 => collect(['text'=>'已支付', 'value'=>2])
        ]);
    }

    public function getStatusTextAttribute(){
        $str = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($str)){
            return $this->getStatus()->get($str)->get("text");
        }
        return null;
    }

    public function getPayType()
    {
        return collect([
            1 => collect(['text'=>'微信支付', 'value'=>1]),
            2 => collect(['text'=>'对公账户', 'value'=>2])
        ]);
    }

    public function getPayTypeTextAttribute(){
        $str = $this->getAttribute("pay_type");
        if($this->getPayType()->offsetExists($str)){
            return $this->getPayType()->get($str)->get("text");
        }
        return null;
    }

    public function getPaymentType()
    {
        return collect([
            1 => collect(['text'=>'分期付款', 'value'=>1]),
            2 => collect(['text'=>'先做后款', 'value'=>2]),
            3 => collect(['text'=>'先款后做', 'value'=>3])
        ]);
    }

    public function getPaymentTypeTextAttribute(){
        $str = $this->getAttribute("payment_type");
        if($this->getPaymentType()->offsetExists($str)){
            return $this->getPaymentType()->get($str)->get("text");
        }
        return null;
    }
}

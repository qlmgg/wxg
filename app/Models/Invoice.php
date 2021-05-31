<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Invoice
 * @package App\Models
 * @property int $user_id 用户ID
 * @property int $company_id 公司ID
 * @property int $type 发票类型 1个人 2企业
 * @property double $money 发票金额
 * @property string $name 收货人姓名
 * @property string $mobile 收货人手机
 * @property string $address 收件地址
 * @property string $invoice_number 发票编号
 * @property int $status 状态 0未开票 1已开票
 * @property int $courier_company 快递公司类型 1顺丰，2申通，3圆通，4中通，5韵达
 * @property string $courier_number 快递单号
 */
class Invoice extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "user_id",
        "company_id",
        "type",
        "money",
        "name",
        "mobile",
        "address",
        "invoice_number",
        "status",
        "courier_company",
        "courier_number"
    ];

    protected $appends = ["status_text","courier_company_text"];

    public function getStatus()
    {
        return collect([
           0=>collect(["text"=>"未开票","value"=>0]),
           1=>collect(["text"=>"已开票","value"=>1])
        ]);
    }

    public function getStatusTextAttribute()
    {
        $status = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($status)){
            if($status==1){
                if(empty($this->getAttribute("courier_number"))){
                    return "已开票-未派送";
                }else{
                    return "已开票-已派送";
                }
            }
            return $this->getStatus()->get($status)->get("text");
        }
        return null;
    }

    public function getCourierCompany()
    {
        //快递公司类型 1顺丰，2申通，3圆通，4中通，5韵达
        return collect([
            1=>collect(["text"=>"顺丰","value"=>1]),
            2=>collect(["text"=>"申通","value"=>2]),
            3=>collect(["text"=>"圆通","value"=>3]),
            4=>collect(["text"=>"中通","value"=>4]),
            5=>collect(["text"=>"韵达","value"=>5])
        ]);
    }

    public function getCourierCompanyTextAttribute()
    {
        $company = $this->getAttribute("courier_company");
        if($this->getCourierCompany()->offsetExists($company)){
            return $this->getCourierCompany()->get($company)->get("text");
        }
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class ContractManagement extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;
    
    protected $fillable = [
        "region_id",
        "check_order_id",
        "enterprise_name",
        "name",
        "mobile",
        "money",
        "signature_date",
        "age_limit",
        "end_date",
        "status",
        "contracts_file",
        "remarks",
        "is_process"
    ];
    
    protected $hidden = [
        'deleted_at'
    ];
    
    protected $appends = [
        "status_text"
    ];

    public function getStatus()
    {
        return collect([
            1 => collect(["text"=>"进行中", "value"=>1]),
            2 => collect(["text"=>"即将过期", "value"=>2]),
            3 => collect(["text"=>"已过期", "value"=>3])
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

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function checkOrder()
    {
        return $this->belongsTo(CheckOrder::class);
    }
}

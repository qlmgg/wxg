<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class CommunicationRecord extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;
    
    protected $fillable = [
        "check_order_id",
        "worker_id",
        "content",
        "estimate_time",
        "status"
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
            -1 => collect(["text"=>"作废", "value"=>-1]),
            1 => collect(["text"=>"继续沟通", "value"=>1])
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
}

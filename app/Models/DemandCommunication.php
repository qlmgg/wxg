<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasDateTimeFormatter;

/**
 * Class DemandCommunication
 * @property $admin_user_id ID
 * @property $demand_id 需求ID
 * @property $content 沟通内容
 * @property $status 1继续沟通 2作废
 * @package App\Models
 */
class DemandCommunication extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "admin_user_id",
        "demand_id",
        "content",
        "door_at",
        "status",
        "communicator_id",
        "communicator_type"
    ];

    protected $appends = ['status_text'];
    protected $casts=[
        "door_at"=>"datetime:Y-m-d H:i",
        "created_at"=>"datetime:Y-m-d H:i"
    ];
    public function getStatus()
    {
        return collect([
            1=>collect(["text"=>"继续沟通","value"=>1]),
            -1=>collect(["text"=>"作废","value"=>-1])
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin_user(){
        return $this->belongsTo(AdminUser::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function demand()
    {
        return $this->belongsTo(Demand::class);
    }

    public function communicator()
    {
        return $this->morphTo();
    }

    public function changeDemandStatus(){
        $status = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($status)){
            $demand = $this->demand()->with([])->first();
        }
    }

}

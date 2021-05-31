<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasDateTimeFormatter;

class Demand extends Model
{
    use HasFactory,SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "code",
        "user_id",
        "company_name",
        "nature_id",
        "region_id",
        "structure_area",
        "name",
        "mobile",
        "address",
        "longitude",
        "latitude",
        "province_text",
        "province_code",
        "city_text",
        "city_code",
        "district_text",
        "district_code",
        "door_at",
        "status",
        "user_demand"
    ];

    protected $appends = ["status_text"];

    public function getStatus()
    {
        return collect([
            0=>collect(["text"=>"待沟通","value"=>0]),
            1=>collect(["text"=>"待上门检查","value"=>1]),
            2=>collect(["text"=>"成功","value"=>2]),
            -1=>collect(["text"=>"已作废","value"=>-1]),
        ]);
    }

    /**
     * @return |null
     */
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
    public function nature()
    {
        return $this->belongsTo(Nature::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function demandCommunication()
    {
        return $this->hasMany(DemandCommunication::class);
    }

    public function changeStatus($status){
        if($this->getStatus()->offsetExists($status)){
            $this->status = $status;
            $this->save();
        }

    }

    public function monthCheckOrder()
    {
        return $this->hasOne(CheckOrder::class, "demand_id");
    }

}

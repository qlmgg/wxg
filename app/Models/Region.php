<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "name",
        "province_text",
        "province_code",
        "city_text",
        "city_code",
        "district_text",
        "district_code",
        "status"
    ];

    protected $appends = ["status_text","region_text"];

    public function getStatus(){
        return collect([
            0=>collect(["text"=>"禁用","value"=>0]),
            1=>collect(["text"=>"启用","value"=>1])
        ]);
    }

    public function getStatusTextAttribute(){
        $status = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($status)){
            return $this->getStatus()->get($status)->get("text");
        }
        return null;
    }

    public function getRegionTextAttribute(){
        return $this->getAttribute("province_text")."-".$this->getAttribute("city_text")."-".$this->getAttribute("district_text");
    }
}

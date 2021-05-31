<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Royalty extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "level",
        "status",
        "customer_money",
        "worker_money",
        "worker_money_for_customer"
    ];

    protected $casts = [
        "created_at"=>"date:Y-m-d H:i"
    ];

    protected $appends = ["level_text","status_text"];

    public function getLevel(){
        return collect([
            1=>collect(["text"=>"小工","value"=>1]),
            2=>collect(["text"=>"中工","value"=>2]),
            3=>collect(["text"=>"大工","value"=>3])
        ]);
    }

    public function getStatus(){
        return collect([
            0=>collect(["text"=>"禁用","value"=>0]),
            1=>collect(["text"=>"启用","value"=>1])
        ]);
    }

    public function workerLevel()
    {
        return $this->belongsTo(Level::class,"level");
    }

    public function getLevelTextAttribute(){
        /*
        $level = $this->getAttribute("level");
        if($this->getLevel()->offsetExists($level)){
            return $this->getLevel()->get($level)->get("text");
        }
        return null;
        */
        return collect($this->workerLevel()->first())->get("name");
    }

    public function getStatusTextAttribute(){
        $status = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($status)){
            return $this->getStatus()->get($status)->get("text");
        }
        return null;
    }

}

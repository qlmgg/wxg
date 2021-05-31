<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;
    protected $fillable = [
      "name",
      "status"
    ];

    protected $casts = [
      "created_at"=>"datetime:Y-m-d H:i:s",
      "updated_at"=>"datetime:Y-m-d H:i:s"
    ];

    protected $appends = ["status_text"];

    public function getStatus()
    {
        return collect([
           0=>collect(["text"=>"禁用","value"=>0]),
           1=>collect(["text"=>"启用","value"=>1])
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

}

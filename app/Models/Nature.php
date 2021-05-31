<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class Nature extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "name",
        "status",
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = ["status_text"];

    public function getStatus()
    {
        return collect([
            0=>collect(["text"=>"禁用","value"=>0]),
            1=>collect(["text"=>"启用","value"=>1])
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
}

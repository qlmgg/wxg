<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;

class Comment extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes, HasDateTimeFormatter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "type",
        "user_id",
        "name",
        "mobile",
        "content",
        "status",
        "push_status",
        "region_id"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = ["types_text", "status_text", "push_status_text"];

    public function getTypes()
    {
        return collect([
            1=>collect(["text"=>"平台反馈","value"=>1]),
            2=>collect(["text"=>"工人反馈","value"=>2]),
            3=>collect(["text"=>"其它反馈","value"=>3]),
        ]);
    }

    public function getTypesTextAttribute()
    {
        $str = $this->getAttribute("type");
        if($this->getTypes()->offsetExists($str)){
            return $this->getTypes()->get($str)->get("text");
        }
        return null;
    }

    public function getStatus()
    {
        return collect([
            0=>collect(["text"=>"未处理","value"=>0]),
            1=>collect(["text"=>"已处理","value"=>1]),
        ]);
    }

    public function getStatusTextAttribute()
    {
        $str = $this->getAttribute("status");
        if($this->getStatus()->offsetExists($str)){
            return $this->getStatus()->get($str)->get("text");
        }
        return null;
    }

    public function getPushStatus()
    {
        return collect([
            0=>collect(["text"=>"未推送","value"=>0]),
            1=>collect(["text"=>"已推送","value"=>1]),
        ]);
    }

    public function getPushStatusTextAttribute()
    {
        $str = $this->getAttribute("push_status");
        if($this->getPushStatus()->offsetExists($str)){
            return $this->getPushStatus()->get($str)->get("text");
        }
        return null;
    }


    /**
     * 获取对应文件
     */
    public function files()
    {
        return $this->hasMany(CommentFiles::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function syncFiles($files)
    {
        $this->syncHasMany($files,$this->files());
    }
}

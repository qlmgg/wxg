<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;

class PushRecords extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter, SyncHasMany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "comment_id",
        "title",
        "type",
        "content",
        "worker_id"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = ["types_text"];

    public function getTypes()
    {
        return collect([
            1=>collect(["text"=>"全公司","value"=>1]),
            2=>collect(["text"=>"区域员工","value"=>2])
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
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * 获取相关文件
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
}

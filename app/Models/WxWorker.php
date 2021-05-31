<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasDateTimeFormatter;

class WxWorker extends Model
{
    use HasFactory,HasDateTimeFormatter,SoftDeletes;

    protected $fillable = [
        "worker_id",
        "nickname",
        "avatar_url",
        "gender",
        "country",
        "province",
        "city",
        "language",
        "openid",
        "unionid",
        'app_id',
        "mobile"
    ];

    public function worker(){
        return $this->belongsTo(Worker::class);
    }

    public function getGenderMap() {
        return collect([
            0 => collect(["value" => 0, "text" => "未知"]),
            1 => collect(["value" => 1, "text" => "男"]),
            2 => collect(["value" => 2, "text" => "女"]),
        ]);
    }

    public function getGenderText($gender) {
        if ($this->getGenderMap()->offsetExists($gender)) {
            return $this->getGenderMap()->get($gender)->get("text");
        }
        return null;
    }

    /**
     * 公众号用户ID
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wxOfficialUser() {
        return $this->hasOne(WxOfficialUser::class, "unionid", "unionid");
    }


}

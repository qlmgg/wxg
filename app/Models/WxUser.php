<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WxUser
 * @package App\Models
 *
 * @property int $id ID
 * @property int $user_id 用户ID
 * @property User $user 用户
 * @property string $nickname 昵称
 * @property string $avatar_url 用户头像
 * @property string $gender 性别
 * @property string $country 国家
 * @property string $province 省份
 * @property string $city 城市
 * @property string $language 语言
 * @property string $openid openid
 * @property string $unionid unionid
 * @property string $app_id 微信app_id
 */
class WxUser extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "user_id",
        "nickname",
        "avatar_url",
        "gender",
        "country",
        "province",
        "city",
        "language",
        "openid",
        "unionid",
        'app_id'
    ];


    /**
     * 用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo(User::class);
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

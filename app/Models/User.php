<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use App\Traits\PassportCache;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 * @property int id ID
 * @property string $username 用户名
 * @property string $name 名称
 * @property string $mobile 手机号
 * @property string $email 邮箱
 * @property string $email_verified_at 邮箱验证时间
 * @property string $password 密码
 * @property string $avatar_url 头像地址
 * @property int $role_id 角色ID
 * @property int $status 状态 1启用 0禁用
 * @property WxUser $wxUser 微信用户相关信息
 *
 * @property int $region_id 区域ID
 * @property string $address 详细地址
 * @property int $type 用户类型 0未认证 1个人 2企业
 * @property string $id_card 身份证号
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, PassportCache, HasDateTimeFormatter, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "username",
        "name",
        "link_name",
        "mobile",
        "email",
        "email_verified_at",
        "password",
        "role_id",
        "status",
        "model_type",
        "model_id",
        "remember_token",
        "avatar_url",
        "region_id",
        "address",
        "type",
        "id_card"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ["type_text"];

    public function getType(){
        return collect([
            0=>collect(["text"=>"未认证","value"=>0]),
            1=>collect(["text"=>"个人用户","value"=>1]),
            2=>collect(["text"=>"企业用户","value"=>2])
        ]);
    }

    public function getTypeTextAttribute(){
        $status = $this->getAttribute("type");
        if($this->getType()->offsetExists($status)){
            return $this->getType()->get($status)->get("text");
        }
        return null;
    }

    public function setWxSessionKey($session_key)
    {
        $this->passportCacheSet("wx_session_key", $session_key);
    }

    /**
     * 获取微信session_key缓存
     * @return string|null
     */
    public function getWxSessionKey()
    {
        return $this->passportCacheGet("wx_session_key");
    }

    /**
     * 微信登录用户
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wxUser()
    {
        return $this->hasOne(WxUser::class);
    }

    public function wxWorker()
    {
        return $this->hasOne(WxWorker::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    /**
     * 获取微信公众号openid
     * @return string|null
     */
    public function getOfficialOpenId(): ?string {
        /**
         * @var WxUser $wxUser
         */
        $wxUser = $this->wxUser()->select(["id", "unionid"])->first();

        if ($wxUser) {
            $wxOfficialUser = $wxUser->wxOfficialUser()->select(["id", "unionid", "openid"])->first();
            return data_get($wxOfficialUser, 'openid');
        }

        return null;
    }
    
    public function getOfficialOpenIdTest(): ?string {
        /**
         * @var WxUser $wxUser
         */
        $wxUser = $this->wxUser()->select(["id", "unionid"])->first();
        /**
         * @var WxWorker $wxWorker
         */
        $wxWorker = $this->wxWorker()->select(["id", "unionid"])->first();

        if (data_get($wxUser, "openid")) {
            $wxOfficialUser = $wxUser->wxOfficialUser()->select(["id", "unionid", "openid"])->first();
            return data_get($wxOfficialUser, 'openid');
        } else if (data_get($wxWorker, "openid")) {
            $wxOfficialUser = $wxWorker->wxOfficialUser()->select(["id", "unionid", "openid"])->first();
            return data_get($wxOfficialUser, 'openid');
        }

        return null;
    }

}

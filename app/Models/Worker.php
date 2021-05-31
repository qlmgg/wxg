<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use App\Traits\PassportCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


/**
 * Class Worker
 * @package App\Models
 *
 * @property int $id ID
 * @property string $name 用户名
 * @property string $mobile 手机号
 * @property int $role_id 角色ID
 * @property int $type 人员类型 1后台管理员 2区域经理
 * @property int $status 1启用 0禁用
 * @property Role $role 角色ID
 *
 */
class Worker extends Authenticatable
{
    use HasFactory, HasApiTokens, PassportCache, SoftDeletes, HasRoles,HasDateTimeFormatter;


    protected $hidden = [
        'password', 'openid'
    ];

    protected function getCachePrefix()
    {
        return "passport_work_cache_";
    }

    // 权限缓存key
    const USER_ROLE_MENU_PERMISSION_KEY = "worker_role_menu_permission_key";

    protected $fillable = [
        "name",
        "mobile",
        "password",
        "openid",
        "entry_at",
        "region_id",
        "level",
        "status",
        "work_status",
        "pre_work_status",
        "rest_reason",
        "role_id",
        "type",
        "is_worker"
    ];
    protected $casts = [
        "entry_at" => "date:Y-m-d",
        "created_at" => "date:Y-m-d H:i"
    ];

    protected $appends = ['level_text', "status_text", "work_status_text", "bind_text", "is_worker_text"];

    public function setWxSessionKey($session_key)
    {
        $this->passportCacheSet("wx_session_key", $session_key);
    }

    // 权限要用的
    public function guardName()
    {
        return ["web", "admin"];
    }

    public function getLevel()
    {
        return collect([
            1 => collect(["text" => "小工", "value" => 1]),
            2 => collect(["text" => "中工", "value" => 2]),
            3 => collect(["text" => "大工", "value" => 3])
        ]);
    }

    public function workerLevel()
    {
        return $this->belongsTo(Level::class,"level");
    }

    public function getStatus()
    {
        return collect([
            0 => collect(["text" => "禁用", "value" => 0]),
            1 => collect(["text" => "启用", "value" => 1])
        ]);
    }

    public function getWorkStatus()
    {
        return collect([
            0 => collect(["text" => "休息中", "value" => 0]),
            1 => collect(["text" => "空闲中", "value" => 1]),
            2 => collect(["text" => "工作中", "value" => 2])
        ]);
    }

    public function getIsWorker()
    {
        return collect([
            0 => collect(["text" => "经理", "value" => 0]),
            1 => collect(["text" => "工人", "value" => 1])
        ]);
    }

    public function getLevelTextAttribute()
    {
        /*
        $level = $this->getAttribute("level");
        if ($this->getLevel()->offsetExists($level)) {
            return $this->getLevel()->get($level)->get("text");
        }
        return null;
        */
        return collect($this->workerLevel()->first())->get("name");
    }

    public function getStatusTextAttribute()
    {
        $status = $this->getAttribute("status");
        if ($this->getStatus()->offsetExists($status)) {
            return $this->getStatus()->get($status)->get("text");
        }
    }

    public function getWorkStatusTextAttribute()
    {
        $status = $this->getAttribute("work_status");
        if ($this->getWorkStatus()->offsetExists($status)) {
            return $this->getWorkStatus()->get($status)->get("text");
        }
        return null;
    }

    public function getIsWorkerTextAttribute()
    {
        $is_worker = $this->getAttribute("is_worker");
        if ($this->getIsWorker()->offsetExists($is_worker)) {
            return $this->getIsWorker()->get($is_worker)->get("text");
        }
        return null;
    }

    public function getBindTextAttribute()
    {
        $openid = $this->getAttribute("openid");
        if (is_null($openid)) {
            return "未绑定";
        } else {
            return "已绑定";
        }
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function wxWorker()
    {
        return $this->hasOne(WxWorker::class);
    }

    public function demandCommunications()
    {
        return $this->morphMany(DemandCommunication::class);
    }

    /**
     * 通过给定的username获取用户实例
     *
     * @param string $username 手机号
     * @return \App\Models\User
     */
    public function findForPassport($username)
    {
        return $this->where('mobile', $username)->first();
    }


    /**
     * 角色
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * 获取公众号openID
     * @return string|null
     */
    public function getOfficialOpenId(): ?string
    {
        /**
         * @var WxWorker $wxWorker
         */
        $wxWorker = $this->wxWorker()->select(["id", "unionid"])->first();

        if ($wxWorker) {
            $wxOfficialUser = $wxWorker->wxOfficialUser()->select(["id", "unionid", "openid"])->first();
            return data_get($wxOfficialUser, 'openid');
        }
        return null;
    }


    /**
     * 获取缓存的菜单权限
     * @return array
     */
    public function getCacheMenuPermission()
    {

        $key = static::USER_ROLE_MENU_PERMISSION_KEY . "_user_id_" . $this->id;

        return Cache::tags([static::USER_ROLE_MENU_PERMISSION_KEY])->get($key, function () use ($key) {

            $menuPermission = [];
            if ($this->role_id == Role::ROLE_PLATFORM_SUPPER) {
                $menuPermission = Menu::with([])->get()->toArray();
            } else { // 其他后台
                $permissions = $this->getAllPermissions();
                $menuPermission = Menu::with([])->whereIn("uri", $permissions->pluck("name")->toArray())
                    ->get()->toArray();
            }

            if (count($menuPermission) > 0) {
                Cache::put($key, $menuPermission, now()->addDay());
            }

            return $menuPermission;
        });
    }

    public function royalty()
    {
        return $this->belongsTo(Royalty::class,"level","level");
    }


    /**
     * 获取微信session_key缓存
     * @return string|null
     */
    public function getWxSessionKey()
    {
        return $this->passportCacheGet("wx_session_key");
    }

    public function monthCheckWorkers()
    {
        return $this->hasMany(MonthCheckWorker::class);
    }

    public function monthCheckWorkerActionDurations()
    {
        return $this->hasMany(MonthCheckWorkerActionDuration::class);
    }

}

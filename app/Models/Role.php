<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as Model;

/**
 * Class Role
 * @package App\Models
 *
 * @property int $id ID
 * @property string $name 名称
 * @property int $status 状态 1启用 0 禁用
 *
 */
class Role extends Model
{
    use SoftDeletes, HasFactory, HasDateTimeFormatter;

    // 总管理员
    const ROLE_PLATFORM_SUPPER = 1;

    // 区域经理
    const ROLE_REGION_SUPPER = 2;


    protected $fillable = [
        "status",
        "name",
        "guard_name",
    ];

    public function getStatus()
    {
        return collect([
            1 => collect(["text" => "启用", "value" => 1]),
            0 => collect(["text" => "禁用", "value" => 0]),
        ]);
    }

    // 权限要用的
    public function guardName()
    {
        return ["web", "admin"];
    }

    // 获取要保护的角色IDs
    public function getProtectRoleIds() {
        return [
            static::ROLE_PLATFORM_SUPPER,
            static::ROLE_REGION_SUPPER,
        ];
    }

    /**
     * 同步后端菜单权限
     * @param $permissions
     */
    public function syncAdminMenu($permissions)
    {
        // 获取当前数据库中的后台菜单权限
        $old_permissions = $this->permissions()
            ->where('guard_name', '=', "admin")->get();

        $diff = $old_permissions->diff($permissions);

        if ($diff->count()) { // 删除旧的
            $this->permissions()->detach($diff);
        }

        $new = $permissions->diff($old_permissions);
        if ($new->count()) {
            $this->givePermissionTo($new);
        }
    }

    /**
     * 后台角色
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function adminPermission() {
        return $this->permissions()
            ->where('guard_name', '=', "admin");
    }

}

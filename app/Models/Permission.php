<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use  Spatie\Permission\Models\Permission as Model;


/**
 * Class Permission
 * @package App\Models
 *
 * @property int $id ID
 * @property int $name 权限
 * @property int $guard_name guardName
 *
 */
class Permission extends Model
{
    use HasFactory, HasDateTimeFormatter;

    // 相关类型
    public function target() {
        return $this->morphTo("target");
    }

    // 权限要用的
    public function guardName() {
        return ["web", "admin"];
    }

}

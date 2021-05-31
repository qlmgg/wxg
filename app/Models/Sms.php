<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Class Sms
 * @package App\Models
 *
 * @property int $id ID
 * @property string $type 短信类型
 * @property string $phone 手机号
 * @property string $drive 驱动
 * @property string $template_code 短信模块
 * @property array $param 参数
 * @property array $result 发送结果
 * @property int $status 状态 0 未使用 1已使用
 */
class Sms extends Model
{
    use HasFactory, Notifiable, HasDateTimeFormatter;


    protected $casts = [
        "param" => "json",
        "result" => "json"
    ];

    protected $fillable = [
        "type",
        "expires_in",
        "phone",
        "drive",
        "template_code",
        "param",
        "result",
        "status",
    ];


    public function getTypes()
    {
        return collect([
            "work_login_code" => collect(["text" => "员工登录", "value" => "work_login_code"])
        ]);
    }

    /**
     * 获取验证码
     * @return string|null
     */
    public function getCode(): ?string {
        return data_get($this->param, "code") ?? null;
    }
}

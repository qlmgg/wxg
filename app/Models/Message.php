<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;


/**
 * Class Message
 * @package App\Models
 *
 * @property int $to_user_id 发送到的用户
 * @property int $to_worker_id 发送到的员工
 * @property string $from_type 发送类型
 * @property string $from_id 发送ID
 * @property string $title 标题
 * @property string $content 内容
 * @property string $type 类型 1 系统 2月检
 * @property Carbon $read_at 已读时间
 * @property Carbon $confirm_at 确认时间
 *
 */
class Message extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;


    protected $fillable = [
        'to_user_id',
        'to_worker_id',
        'from_type',
        'from_id',
        'title',
        'content',
        'type',
        'read_at',
        'confirm_at',
        "can_confirm"
    ];

    /**
     * 用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toUser() {
        return $this->belongsTo(User::class, "to_user_id");
    }

    /**
     * 员工
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toWorker() {
        return $this->belongsTo(Worker::class, "");
    }

}

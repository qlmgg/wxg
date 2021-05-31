<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    const MODULE_NAME_REGION = "区域管理";
    const MODULE_NAME_WORKER = "工人管理";
    const MODULE_NAME_ROYALTY = "提成管理";
    const MODULE_NAME_CUSTOMER = "客户管理";
    const MODULE_NAME_INVOICE = "发票管理";
    const MODULE_NAME_DEMAND = "需求管理";
    const MODULE_NAME_LEVEL = "级别管理";

    protected $fillable = [
        'log_name',
        'description',
        'subject_id',
        'subject_type',
        'causer_id',
        'causer_type',
        'properties',
        'ip',
        'url',
        'request_body',
    ];


    protected $casts = [
        'properties' => 'json',
        'request_body' => 'json',
    ];

    /**
     * 为数组 / JSON 序列化准备日期。
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 操作的人
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function causer()
    {
        return $this->morphTo('causer');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * 操作的对象
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject()
    {
        return $this->morphTo('subject');
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;

class JobContent extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "check_order_id",
        "month_check_id",
        "month_check_worker_action_id",
        "month_check_worker_id",
        "title",
        "remarks",
        "worker_id",
        "type"
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
    public function files()
    {
        return $this->hasMany(JobContentFiles::class);
    }

    public function syncFiles(array $files)
    {
        $this->syncHasMany($files,$this->files());
    }
}

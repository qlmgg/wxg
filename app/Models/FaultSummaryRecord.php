<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;

class FaultSummaryRecord extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "check_order_id",
        "month_check_id",
        "month_check_worker_id",
        "worker_id",
        "title",
        "status"
    ];

    protected $hidden = [
        "deleted_at"
    ];

    protected $appends = [
        "status_text"
    ];

    public function getStatus()
    {
        return collect([
            0 => collect(["text" => "未处理", "value" => 0]),
            1 => collect(["text" => "免费处理", "value" => 1])
        ]);
    }

    public function getStatusTextAttribute()
    {
        $str = $this->getAttribute("status");
        if ($this->getStatus()->offsetExists($str)) {
            return $this->getStatus()->get($str)->get("text");
        }
        return null;
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function files()
    {
        return $this->hasMany(FaultSummaryRecordFlies::class);
    }

    public function syncFiles(array $files)
    {
        $this->syncHasMany($files,$this->files());
    }
}

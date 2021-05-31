<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SyncHasMany;

class SiteConditions extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "check_order_id",
        "month_check_id",
        "month_check_worker_id",
        "worker_id",
        "remarks",
        "type"
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function files()
    {
        return $this->hasMany(SiteConditionsFiles::class);
    }

    public function syncFiles(array $files)
    {
        $this->syncHasMany($files,$this->files());
    }

}

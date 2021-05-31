<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;

class Contracts extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "check_order_id",
        "month_check_id",
        "month_check_worker_id",
        "remarks",
        "worker_id",
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function files()
    {
        return $this->hasMany(ContractsFiles::class);
    }

    public function syncFiles(array $files)
    {
        $this->syncHasMany($files,$this->files());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;
use App\Traits\SyncHasMany;

class CheckOrderComments extends Model
{
    use SyncHasMany,HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "user_id",
        "month_check_id",
        "check_order_id",
        "content"
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function files()
    {
        return $this->hasMany(CheckOrderCommentsFiles::class);
    }

    public function syncFiles($files)
    {
        $this->syncHasMany($files,$this->files());
    }
}

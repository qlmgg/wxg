<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthCheckWorkerActionFile extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
      "month_check_worker_action_id",
      "big_file_id",
      "name",
      "url"
    ];

    public function monthCheckWorkerAction()
    {
        return $this->belongsTo(MonthCheckWorkerAction::class);
    }

}

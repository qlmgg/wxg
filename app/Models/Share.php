<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Share extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "user_id",
        "share_user_id"
    ];
    protected $casts = [
      "created_at"=>"date:Y-m-d H:i:s"
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shareUser()
    {
        return $this->belongsTo(User::class,"share_user_id","id");
    }

}

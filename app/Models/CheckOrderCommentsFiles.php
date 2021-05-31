<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class CheckOrderCommentsFiles extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "check_order_comments_id",
        "big_file_id",
        "name",
        "url"
    ];


    protected $hidden = [
        'deleted_at'
    ];

    public function bigFile()
    {
        return $this->belongsTo(BigFile::class);
    }
}

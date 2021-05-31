<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectOrderRecordFile extends Model
{
    use HasFactory,SoftDeletes,HasDateTimeFormatter;

    protected $fillable = [
        "reject_order_record_id",
        "big_file_id",
        "name",
        "url"
    ];

}

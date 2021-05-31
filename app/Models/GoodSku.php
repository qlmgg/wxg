<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class GoodSku extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "goods_id",
        "name",
        "price"
    ];

    protected $hidden = [
        'deleted_at'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DemandOrderStatistics extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "region_id",
        "submit_num",
        "process_num"
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format("Y-m-d");
    }
}

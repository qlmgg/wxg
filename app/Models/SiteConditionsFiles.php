<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class SiteConditionsFiles extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "site_conditions_id",
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class FixedInspectionRecord extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "check_order_id",
        "fixed_check_items_id"
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function fixedCheckItems()
    {
        return $this->belongsTo(FixedCheckItems::class);
    }
}

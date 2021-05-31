<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class SystemParameters extends Model
{
    use HasFactory, HasDateTimeFormatter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "mobile",
        "landline_number",
        "activity_description",
        "account",
        "open_account_bank",
        "remarks"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at'
    ];
    
}

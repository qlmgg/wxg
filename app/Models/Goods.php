<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDateTimeFormatter;

class Goods extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "brand_id",
        "title",
        "price",
        "unit",
        "remark",
        "status"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    // 获取管理规格
    public function goodSku()
    {
        return $this->hasMany(GoodSku::class);
    }

    /**
     * 获取对应文件
     */
    public function files()
    {
        return $this->hasMany(GoodFiles::class);
    }
}

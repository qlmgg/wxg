<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'text',
        'parent_code',
        'parent_text',
        'level',
        'status',
    ];


    public function parent()
    {
        return $this->belongsTo(Area::class, 'parent_code', 'code');
    }


    /**
     * @param array $arr_code code库
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTextByCodes(array $arr_code)
    {
        return $this->with([])
            ->whereIn('code', $arr_code)
            ->get(['id', 'code', 'text']);
    }

}

<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BigFile extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $fillable = [
        'sha1',
        'size',
        'path',
        'content_type',
        'client_original_name',
        'extension'
    ];


    protected $officeExtensions = [
        'doc',
        'docx',
        'docm',
        'dotx',
        'dotm',
        'xls',
        'xlsx',
        'xlsm',
        'xltx',
        'xltm',
        'xlsb',
        'xlam',
        'ppt',
        'pptx',
        'pptm',
        'ppsx',
        'ppsm',
        'potx',
        'potm',
        'ppam',
    ];


    protected $imageExtensions = [
        'jpg',
        'jpeg',
        'png',
        'bmp',
        'gif'
    ];

    protected $disk = 'oss';

    protected $casts = [
        'is_exist' => 'boolean'
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {

        $str = Str::finish(config("filesystems.disks.{$this->disk}.url"), '/') . $this->path;

        if (in_array($this->id, [1, 2])) { // 就是拷贝来的数据，简单处理一下
            $str = str_replace("mzmtoogu", "tkpx", $str);
        }

        return $str;
    }

    public function isOffice()
    {
        if ($this->extension) {
            return in_array(strtolower($this->extension), $this->officeExtensions);
        }
        return false;
    }

    public function isImage()
    {
        if ($this->extension) {
            return in_array(strtolower($this->extension), $this->imageExtensions);
        }
        return false;
    }

    /**
     * 获取map 形式的图片
     * @param array $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMapByIds(array $ids)
    {
        $list = $this->with([])->whereIn("id", $ids)->get();
        return $list->keyBy("id");
    }
}

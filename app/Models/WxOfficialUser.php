<?php

namespace App\Models;

use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WxOfficialUser extends Model
{
    use HasFactory, HasDateTimeFormatter;

    protected $fillable = [
        "subscribe",
        "openid",
        "app_id",
        "nickname",
        "sex",
        "country",
        "province",
        "city",
        "language",
        "headimgurl",
        "subscribe_at",
        "unionid",
        "remark",
        "groupid",
        "tagid_list",
        "subscribe_scene",
        "qr_scene",
        "qr_scene_str",
    ];

    protected $dates = [
        "subscribe_at"
    ];

    protected $casts = [
        "tagid_list" => 'json'
    ];

    /**
     * 将微信的元素数据转换成本地数据
     * @param $origin
     * @return array|null
     */
    public static function convertOrigin($origin): ?array
    {
        if ($origin['subscribe']) {
            // 转换时间
            if (isset($origin["subscribe_time"]) && $origin["subscribe_time"]) {
                $origin['subscribe_at'] = Carbon::createFromTimestamp($origin["subscribe_time"]);
            }
            // 转换性别
            if (isset($origin['sex'])) {
                switch ($origin['sex']) {
                    case 1:
                        $origin['sex'] = '男';
                        break;
                    case 2:
                        $origin['sex'] = '女';
                        break;
                    default:
                        $origin['sex'] = '未知';
                }
            }


            if (isset($origin['tagid_list']) && !is_array($origin['tagid_list'])) {
                unset($origin['tagid_list']);
            }

            return $origin;
        }
        return null;
    }
}

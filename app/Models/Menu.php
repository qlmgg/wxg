<?php

namespace App\Models;

use App\Events\MenuChangePidsEvent;
use App\Exceptions\NoticeException;
use App\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Menu
 * @package App\Models
 * @property int $id ID
 * @property string $name 名称
 * @property string $uri 地址
 * @property string $icon_class 图标class
 * @property int $type 类型 1:侧边 2按钮, 3占位
 * @property int $p_id 上级ID
 * @property string $method 请求方法
 * @property string $permissions 相关权限
 *
 */
class Menu extends Model
{
    use HasFactory, SoftDeletes, HasDateTimeFormatter;

    protected $fillable = [
        "name",
        "uri",
        "icon_class",
        "type",
        "p_id",
        "method",
    ];

    public function getMethods()
    {
        return collect([
            "GET" => collect(["text" => "GET", "value" => "GET"]),
            "POST" => collect(["text" => "POST", "value" => "POST"]),
            "PUT" => collect(["text" => "PUT", "value" => "PUT"]),
            "DELETE" => collect(["text" => "DELETE", "value" => "DELETE"]),
        ]);
    }

    public function getTypes()
    {
        return collect([
            1 => collect(["text" => "侧边菜单", "value" => 1]),
            2 => collect(["text" => "按钮", "value" => 2]),
            3 => collect(["text" => "占位", "value" => 3]),
        ]);
    }


    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function permissions()
    {
        return $this->morphMany(Permission::class, "target");
    }


    /**
     * @param Menu $end
     * @param String $type before: 在end节点前 after: 在end节点后 inner: 放到end节点里
     * @return $this
     * @throws NoticeException
     */
    public function sortMenu(Menu $end, $type)
    {
        if (empty($this->id)) {
            throw new NoticeException('菜单对象未取得');
        }
        $oldPid = $this->p_id;
        switch ($type) {
            case 'before':
                $this->p_id = $end->p_id;
                $this->save();
                // 排列一下同级上面的数据
                $this->sortOnLevel($type, $end);
                break;
            case 'after':
                $this->p_id = $end->p_id;
                $this->save();
                // 排列一下同级下面的数据
                $this->sortOnLevel($type, $end);
                break;
            case 'inner':
                $this->p_id = $end->id;
                $this->save();
                // 修改一下pids和他下面的pids
                break;
        }
        $this->triggerMenuChangePidsEvent($oldPid);
        return $this;
    }

    public function triggerMenuChangePidsEvent($oldPid)
    {
        if ($oldPid != $this->p_id) {
            event(new MenuChangePidsEvent($this));
        }
    }

    /**
     * 排列一下同级的数据
     * @param $type
     * @param Menu $end
     */
    public function sortOnLevel($type, Menu $end)
    {
        $_this = $this;
        $collection = Menu::where('p_id', '=', $_this->pid)->orderBy('sort', 'desc')->orderBy('id', 'desc')->get()->filter(function ($item) use ($_this) {
            return $_this->id != $item->id;
        });

        $newCollection = collect();

        foreach ($collection as $key => $value) {
            if ($value->id == $end->id) {
                // 这里的的before 和 after 要返排序，因为要反序
                if ($type == 'before') {
                    $newCollection->push($_this);
                    $newCollection->push($value);
                } elseif ($type == 'after') {
                    $newCollection->push($value);
                    $newCollection->push($_this);
                }
            } else {
                $newCollection->push($value);
            }
        }

        return $newCollection->reverse()->values()->each(function ($item, $key) {
            $item->sort = $key;
            $item->save();
        });
    }

    /**
     * 修改相关的pids
     */
    public function changePids()
    {
        $pids = $this->pid ? $this->findOrFail($this->p_id)->pids : '0';
        $this->pids = $pids . ',' . $this->id;
        $this->save();
        // 下面所有的节点
        $this->where('p_id', '=', $this->id)->get()->each(function ($info) {
            event(new MenuChangePidsEvent($info));
        });
    }

    public function updatePids()
    {
        if (empty($this->id)) {
            return;
        }
        $pids = $this->p_id ? $this->find($this->p_id)->pids : '0,';
        $this->pids = $pids . $this->id . ",";
        $this->save();
    }
}

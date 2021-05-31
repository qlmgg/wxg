<?php
/**
 * Created by PhpStorm.
 * User: 56301
 * Date: 2019/7/19
 * Time: 19:35
 */

namespace App\Traits;


use Illuminate\Database\Eloquent\Relations\HasMany;

trait SyncHasMany
{
    public function syncHasMany(array $arr, HasMany $hasMany) {
        $changes = [
            'attached' => [], 'detached' => [], 'updated' => [],
        ];

        $cols = collect($arr);

        $has_ids = $cols->map(function ($item) {
            return data_get($item, 'id');
        })->filter();

        $current = $hasMany->get()->toBase();

        $detach = $current->filter(function ($item) use ($has_ids) {
            return !$has_ids->contains(data_get($item, 'id'));
        });

        // 删除的
        if ($detach->count()) {

            $changes['detached'] = $detach;

            $ids = $detach->map(function ($item) {
                return data_get($item, 'id');
            });

            $hasMany->whereIn('id', $ids)->delete();
        }

        list($updated, $attached) = $cols->partition('id');

        // 添加的
        if ($attached->count()) {
            $createMany = $hasMany->createMany($attached->toArray());

            $changes['attached'] = $createMany;
        }

        // 更新的
        if ($updated->count()) {
            $currentKey = $current->keyBy('id');
            $changes['updated'] = $updated->map(function ($item) use ($currentKey) {

                $id = data_get($item, 'id');

                $model_many = $currentKey->get($id);

                if ($model_many) {
                    $bo = $model_many->update($item);
                    if ($bo) {
                        return $model_many;
                    }
                }

                return null;
            })->filter();
        }

        return $changes;
    }
}

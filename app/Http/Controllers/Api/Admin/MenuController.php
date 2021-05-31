<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\ClearUserCachePermissionEvent;
use App\Events\MenuUpdateEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\Menu;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuController extends SimpleController
{

    protected function getModel()
    {
        return new Menu();
    }

    protected function search(Request $request): Builder
    {
        $user = $request->user();
        if (data_get($user, "type") != 1) {
            throw new NoticeException("无操作权限");
        }

        $model = $this->getModel();
        $model = $model->with([]);

        if ($name = $request->input("name")) {
            $model->where("name", "like", "%{$name}%");
        }
        if ($type = $request->input("type")) {
            $model->where("type", "=", $type);
        }
        if ($method = $request->input("method")) {
            $model->where("method", "=", $method);
        }

        return $model;
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $model = $this->getModel();

        $data = $this->validate($request, [
            "uri" => ["required_unless:type,3", "string", "max:255"],
            "name" => ["required", "string", "max:255"],
            "icon_class" => ["nullable", "max:255"],
            "type" => ["required", "in:" . $model->getTypes()->keys()->join(",")],
            "p_id" => ["integer"],
            "method" => ["nullable", "in:" . $model->getMethods()->keys()->join(",")]
        ]);

        if (isset($data['icon_class']) && is_null($data['icon_class'])) {
            unset($data['icon_class']);
        }
        if (isset($data['method']) && is_null($data['method'])) {
            unset($data['method']);
        }


        event(new ClearUserCachePermissionEvent());

        $create = DB::transaction(function () use ($model, $data) {
            $create = $model->with([])->create($data);

            $create->updatePids();

            return $create;
        });
        // 设置pids
        log_action($create, "新增菜单", "菜单管理");
        return $create;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $model = $this->getModel();

        $data = $this->validate($request, [
            "uri" => ["required_unless:type,3", "string", "max:255"],
            "name" => ["required", "string", "max:255"],
            "icon_class" => ["nullable", "max:255"],
            "type" => ["required", "in:" . $model->getTypes()->keys()->join(",")],
            "p_id" => ["integer"],
            "method" => ["nullable", "in:" . $model->getMethods()->keys()->join(",")]
        ]);

        if (is_null($data['icon_class'])) {
            unset($data['icon_class']);
        }
        if (is_null($data['method'])) {
            unset($data['method']);
        }

        if($id == $data["p_id"]) {
            throw new NoticeException("自己的上级不能自己");
        }

        $find = $this->getModel()->with([])->findOrFail($id);

        $origin = clone $find;

        $find = DB::transaction(function () use ($find, $data) {
            $find->update($data);

            $find->updatePids();

            return $find;
        });

        event(new MenuUpdateEvent($find));

        event(new ClearUserCachePermissionEvent());
        log_action($find, "修改菜单", "菜单管理", $origin);

        return $find;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $find = $this->getModel()->with([])->findOrFail($id);
        $origin = clone $find;
        $find->delete();

        log_action($find, "删除菜单", "菜单管理", $origin);

        return SimpleResponse::success("删除成功");
    }

    /**
     * 获取支持的方法类型
     * @return \Illuminate\Support\Collection
     */
    public function methods()
    {
        $model = $this->getModel();

        return $model->getMethods()->values();
    }

    /**
     * 获取菜单的类型
     * @return \Illuminate\Support\Collection
     */
    public function types()
    {
        $model = $this->getModel();

        return $model->getTypes()->values();
    }

    /**
     * 所有菜单
     * @return Collection
     */
    public function all()
    {
        return $this->getModel()->with([])
            ->orderBy("sort", "desc")
            ->orderBy("id", "asc")->get();
    }


    public function allApi()
    {
        return get_route_information_list()->filter(function ($item) {
            return Str::startsWith($item["prefix"], "api");
        })->values();
    }


    /**
     * 排序
     * @param Request $request
     * @param $id
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sort(Request $request, $id)
    {
        /**
         * @var $find Menu
         */
        $find = $this->getModel()->with([])->findOrFail($id);

        $this->validate($request, [
            'end_id' => ['required'],
            'type' => ['required', 'in:before,after,inner']
        ]);

        $end = $this->getModel()->with([])->findOrFail($request->input('end_id'));

        // 排序
        $find->sortMenu($end, $request->input('type'));

        return SimpleResponse::success('排序成功');
    }

}

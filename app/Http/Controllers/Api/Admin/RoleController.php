<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\ClearUserCachePermissionEvent;
use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SimpleResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RoleController extends SimpleController
{
    protected function getModel()
    {
        return new Role();
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

        $status = $request->input("status");
        if (!is_null($status) && in_array($status, [0, 1])) {
            $model->where("status", '=', $status);
        }


        return $model;
    }


    protected function setPermissionByMenuIds(Role $role, array $menu_ids)
    {
        //
        $menus = Menu::with([])->whereIn("id", $menu_ids)->get();

        $permissions = $menus->map(function (Menu $menu) {
            return Permission::with([])->updateOrCreate([
                "target_id" => $menu->id,
                "target_type" => get_class($menu)
            ], [
                "name" => $menu->uri,
                "guard_name" => "admin"
            ]);
        });

        $role->syncAdminMenu($permissions);

        return $permissions;
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
            "name" => ["required", "max:255"],
            "status" => ["required", "in:" . $model->getStatus()->keys()->join(",")],
            "menu_ids" => ["array"],
            "menu_ids.*" => ["integer"],
        ]);

        $data["guard_name"] = "admin";

        $count = Role::with([])
            ->where("name", "=", data_get($data, "name"))
            ->count();

        if ($count) {
            throw new NoticeException("当前角色已存在");
        }

        $create = $model->with([])->create($data);

        $this->setPermissionByMenuIds($create, $data["menu_ids"]);


        log_action($create, "创建角色-".data_get($create, "name"), "角色管理");

        event(new ClearUserCachePermissionEvent());
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
        return $this->getModel()->with(["adminPermission"])->findOrFail($id);
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
            "name" => ["required", "max:255"],
            "status" => ["required", "in:" . $model->getStatus()->keys()->join(",")],
            "menu_ids" => ["array"],
            "menu_ids.*" => ["integer"],
        ]);

        $data["guard_name"] = "admin";


        $count = Role::with([])
            ->where("name", "=", data_get($data, "name"))
            ->where("id", "<>", $id)
            ->count();

        if ($count) {
            throw new NoticeException("当前角色已存在");
        }

        /**
         * @var Role $find
         */
        $find = $this->getModel()->with([])->findOrFail($id);
        $origin = clone $find;

        if (in_array($id, $model->getProtectRoleIds())) {
            if ($find->name != data_get($data, "name")) {
                throw new NoticeException("预留角色不可修改名称");
            }

            if ($find->status != data_get($data, "status") ) {
                throw new NoticeException("预留角色不可修改状态");
            }
        }

        $find->update($data);

        $this->setPermissionByMenuIds($find, $data["menu_ids"]);

        log_action($find, "编辑角色-".data_get($find, "name"), "角色管理", $origin);
        event(new ClearUserCachePermissionEvent());
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

        if (in_array($id, $this->getModel()->getProtectRoleIds())) {
            throw new NoticeException("预留角色不可删除");
        }

        $find = $this->getModel()->with([])->findOrFail($id);
        $origin = clone $find;
        $find->delete();
        log_action($find, "删除角色-".data_get($find, "name"), "角色管理", $origin);
        event(new ClearUserCachePermissionEvent());
        return SimpleResponse::success("删除成功");
    }


    /**
     * 设置状态
     * @param Request $request
     * @param $id
     * @return User
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setStatus(Request $request, $id)
    {
        $data = $this->validate($request, [
            "status" => ["required", "in:0,1"]
        ]);

        if (in_array($id, $this->getModel()->getProtectRoleIds())) {
            throw new NoticeException("预留角色不可修改状态");
        }

        $find = $this->getModel()->with([])->findOrFail($id);
        $origin = clone $find;
        $find->status = data_get($data, "status");
        $find->save();
        log_action($find, "修改状态-".data_get($find, "name"), "角色管理", $origin);
        event(new ClearUserCachePermissionEvent());
        return $find;
    }
}

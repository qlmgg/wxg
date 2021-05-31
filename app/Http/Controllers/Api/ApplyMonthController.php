<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\ApplyMonthUser;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Rules\IdCardRule;
use App\Rules\MobileRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplyMonthController extends Controller
{

    protected function getModel() {
        return new ApplyMonthUser();
    }

    public function getInfo(Request $request) {
        $data = $this->validate($request, [
            "project_id" => ["required", "integer"]
        ]);

        $project = Project::with(["projectRoles"])->findOrFail($data["project_id"]);

        return $project;
    }


    public function store(Request $request) {
        $data = $this->validate($request, [
            "project_id" => ["required", "integer"],
            "name" => ["required", "string", "max:255"],
            "mobile" => ["required", new MobileRule()],
            "id_card" => ["nullable", new IdCardRule()],
            "role_id" => ["required", "integer"]
        ]);

        // todo 角色的验证

        /**
         * @var Project $project
         */
        $project= Project::with([])->findOrFail($data["project_id"]);
        $projectRoleCount = $project->projectRoles()
            ->where("role_id", '=', $data["role_id"])->count();
        if (empty($projectRoleCount)) {
            throw new NoticeException("当前项目角色不存在");
        }

        /**
         * @var User $user
         */
        $user = $request->user();

        $data["user_id"] = $user->id;

        $model = $this->getModel();

        $userIdCount = $model->with([])
            ->where("user_id", "=", $data["user_id"])
            ->where("project_id", "=", $data["project_id"])
            ->count();

        if (!empty($userIdCount)) {
            throw new NoticeException("当前微信账号已申请本项目月卡，无需重复申请");
        }

        // 人员验证
        /**
         * @var ProjectUser $projectUser
         */
        $projectUser = ProjectUser::with([])
            ->where("project_id", '=', $data["project_id"])
            ->where("mobile", '=', $data["mobile"])
            ->where("role_id", '=', $data["role_id"])
            ->orderBy("id", "desc")
            ->first();

        if (empty($projectUser)) {
            throw new NoticeException("根据手机匹配角色，未匹配成功");
        }

        $create = DB::transaction(function()use ($projectUser, $model, $data) {
            /**
             * @var ApplyMonthUser $create
             */
            $create = $model->with([])->create($data);
            $projectUser->user_id = $create->user_id;
            $projectUser->save();
            return $create;
        });



        log_action($create, "申请月卡");
        return $create;
    }
}

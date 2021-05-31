<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\ApplyMonthUser;
use App\Models\Worker;
use Illuminate\Http\Request;

class ApplyMonthController extends Controller
{

    protected function getModel()
    {
        return new ApplyMonthUser();
    }

    public function showAndLock(Request $request, $id)
    {
        /**
         * @var $worker Worker
         */
        $worker = $request->user();

        /**
         * @var ApplyMonthUser $find
         */
        $find = $this->getModel()->with(["role"])->findOrFail($id);

        if ($find->is_doing == 1 && $find->worker_id != $worker->id) {
            throw new NoticeException("当前月卡申请由[" . $worker->name . "]在办理");
        }

        $find->is_doing = 1;
        $find->worker_id = $worker->id;
        $find->save();

        $data = $find->toArray();

        // 获取原月租
        $projectUser = $find->getProjectUser();

        if (!empty($projectUser)) {
            $data['project_user'] = $projectUser->toArray();
        }

        return $data;
    }

}

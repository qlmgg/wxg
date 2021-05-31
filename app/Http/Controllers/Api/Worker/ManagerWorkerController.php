<?php

namespace App\Http\Controllers\Api\Worker;

use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ManagerWorkerController extends SimpleController
{
    //
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new Worker();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $model = $this->getModel()->with(['wxWorker']);
        $work_status=data_get($query,"work_status");
        if(!is_null($work_status)){
            $model = $model->where("work_status","=",$work_status);
        }

        $model = $model->where("region_id","=",$user->region_id);
        return $model;
    }
}

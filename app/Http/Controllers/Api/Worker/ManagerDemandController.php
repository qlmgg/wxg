<?php

namespace App\Http\Controllers\Api\Worker;

use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\Demand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ManagerDemandController extends SimpleController
{
    //
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return  new Demand();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $model = $this->getModel();
        $model = $model->with(['region'])->where("region_id","=",$user->region_id)
                ->where(function($query){
                    $query->where("status","=",0)->orWhere("status","=",1);
                });
        return $model;
    }
}

<?php

namespace App\Http\Controllers\Api\Worker;

use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\CheckOrder;
use App\Models\ContractManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ManagerContractController extends SimpleController
{

    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new ContractManagement();
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
        $model = $model->with(['checkOrder'])->where("region_id","=",$user->region_id)->orderBy("end_date","asc");
        return $model;
    }

}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SystemParameters;
use App\Models\SimpleResponse;
use Illuminate\Http\Request;
use App\Rules\MobileRule;

class SystemParametersController extends Controller
{
    //
    protected function getModel()
    {
        return new SystemParameters();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $user = $request->user();
        if (data_get($user, "type") != 1) {
            throw new NoticeException("无操作权限");
        }
        return $this->getModel()
            ->with([])
            ->where("id", "=", 1)
            ->first();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        //
        $data = $this->validate($request, [
            "mobile" => ["required", new MobileRule()],
            "landline_number" => ["required", "string"],
            "activity_description" => ["nullable", "string"],
            "account" => ["required", "string"],
            "open_account_bank" => ["required", "string"],
            "remarks" => ["nullable", "string"]
        ]);
        
        $find = $this->getModel()->with([])
            ->where("id", "=", 1)->first();
        if($find){
            $old = clone $find;
            if($find->update($data)){
                log_action($find, "系统参数编辑", "系统参数", $old);
                return SimpleResponse::success("编辑成功");
            }
        } else {
            $create = $this->getModel()->with([])->create($data);
            if ($create) {
                log_action($create, "系统参数添加", "系统参数");
                return SimpleResponse::success("添加成功");
            }
        }

        return SimpleResponse::error("操作失败");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        return false;
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        //
        return false;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
        return false;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemParameters  $systemParameters
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemParameters $systemParameters)
    {
        //
        return false;
    }
}

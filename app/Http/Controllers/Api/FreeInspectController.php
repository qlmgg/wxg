<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\EvaluationManagement;
use App\Models\FreeInspect;
use App\Models\FreeInspectContract;
use App\Models\FreeInspectFault;
use App\Models\FreeInspectRecord;
use App\Models\FreeInspectScene;
use App\Models\FreeInspectStaffFlow;
use App\Models\MaterialList;
use App\Models\SimpleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FreeInspectController extends SimpleController
{
    public function getModel()
    {
        return new FreeInspect();
    }

    public function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data):Builder
    {
        $model = $this->getModel();

        $model = $model->with([]);
        $user = Auth::user();
        $model = $model->where("user_id","=",$user->id);
        return $model;
    }

    public function show($id)
    {
        //关联 工人信息 故障汇总单 现场情况 合同情况 赠送情况 评价记录
        return $this->getModel()->with(['nature','region','FreeInspectStaff.worker','FreeInspectFault','FreeInspectScene','FreeInspectContract','MaterialList',"EvaluationManagement.EvaluationManagementFiles.bigFile"])->findOrFail($id);
    }

    public function evaluate(Request $request)
    {
        $data = $this->validate($request,[
            "free_inspect_id"=>["required","integer"],
            "content"=>["required","string","max:255"],
            "files"=>["nullable"],
            "files.*.big_file_id"=>["required_with:files","integer"],
            "files.*.name"=>["required_with:files","string","max:255"]
        ]);

        //先创建评论然后同步评价文件
        return DB::transaction(function() use ($data){
            $create = EvaluationManagement::with([])->create($data);
            $files = data_get($data,"files");
            if(!empty($files)){
                $files = collect($files)->map(function($item) use($create){
                    $item['evaluation_management_id'] = $create->id;
                    //获取文件信息
                    $file = $this->getFile($item["big_file_id"]);
                    $item['url'] = $file->url;
                    return $item;
                });
                $create->syncFiles($files->toArray());
            }
            return SimpleResponse::success("评价成功");
        });

    }


    /**
     * 根据ID获取文件信息
     * @param Request $request
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }


    public function getInspectDetails(Request $request)
    {
        $data = $this->validate($request,[
            "free_inspect_id"=>["required","integer"],
            //fault故障情况 scene现场情况 record赠送记录 contract合同情况 evaluate评价
            "type"=>["required","string","in:fault,scene,material_list,contract,evaluate"]
        ]);
        switch ($data['type']){
            case "fault":
                $model = new FreeInspectFault();
                $model = $model->with(['getFiles']);
                break;
            case "scene":
                $model = new FreeInspectScene();
                $model = $model->with(['getFiles']);
                break;
            case "material_list":
                $model = new MaterialList();
                break;
            case "contract":
                $model = new FreeInspectContract();
                $model = $model->with(['getFiles']);
                break;
            case "evaluate":
                $model = new EvaluationManagement();
                $model = $model->with(['EvaluationManagementFiles']);
                break;
            default:
                $model = null;

        }
        if(is_null($model)) return null;
        $model = $model->where("free_inspect_id","=",data_get($data,"free_inspect_id"));
        if ($request->header('simple-page') == 'true') {
            return $model->simplePaginate($request->input("per-page", 15));
        } else {
            return $model->paginate($request->input("per-page", 15));
        }

       //$model->where("free_inspect_id","=",data_get($data,"free_inspect_id"))->get();
    }


    public function getStaffInfo(Request $request)
    {
        $data = $this->validate($request,[
            "free_inspect_staff_id"=>['required','integer'],
            "worker_id"=>["required","integer"]
        ]);

        return FreeInspectStaffFlow::with([])->where("free_inspect_staff_id","=",data_get($data,"free_inspect_staff_id"))
                ->where("worker_id","=",data_get($data,"worker_id"))->get();

    }

}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Rules\IdCardRule;
use App\Rules\MobileRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends SimpleController
{
    //
    public function getModel()
    {
        return new User();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder
    {
        $model = $this->getModel();
        $model = $model->with(["region:id,name","wxUser","company"]);//累计工时，累计接单

        $user = Auth::user();
        if($user->type==2){
            $model =$model->where("region_id","=",$user->region_id);
        }

        //根据姓名搜索
        if($name = data_get($data,"name")){
            $model->where("name","like","%{$name}%");
        }

        //根据姓名搜索
        if($link_name = data_get($data,"link_name")){
            $model->where("link_name","like","%{$link_name}%");
        }

        //根据手机号码搜索
        if($mobile = data_get($data,"mobile")){
            $model->where("mobile","like","%{$mobile}%");
        }
        //根据微信昵称搜索
        if($nickname = data_get($data,"nickname")){
            $model->whereHas("wxUser",function ($query)use($nickname){
                $query->where("nickname","like","%{$nickname}%");
            });
        }
        //根据认证状态搜索
        if(-1<($type = data_get($data,"type"))){
            $model->where("type","=",$type);
        }

        return $model;

    }

    /**
     * @param $id
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function show($id)
    {
        return $this->getModel()->with(['wxUser',"company"])->findOrFail($id);
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {

        $data = $this->validate($request,[
            "link_name"=>["required","string","max:255"],
            "mobile"=>new MobileRule(),
            //"region_id"=>["required","integer"],
            "address"=>["required","string","max:255"],
            "type"=>["required","integer","in:1,2"],
            "id_card"=>["required_without:company","required_if:type,1",new IdCardRule()],
            "company"=>["required_without:id_card","required_if:type,2"],
            "company.name"=>["required_with:company","string","max:255"],
            "company.tax_number"=>["required_with:company","regex:/^[A-Z0-9]{15}$|^[A-Z0-9]{17}$|^[A-Z0-9]{18}$|^[A-Z0-9]{20}$/"]
        ]);

        $user = Auth::user();
        if($user->type==2){
            $data['region_id'] =$user->region_id;
        }else{
            $this->validate($request,[
                "region_id"=>["required","integer"]
            ]);
            $data["region_id"] = $request->input("region_id");
        }

        //验证手机

        $m_count = $this->getModel()->with([])->where("mobile","=",data_get($data,"mobile"))->count();
        if($m_count>0){
            throw new NoticeException("手机号已被占用");
        }


        //如果是个人用户，判断身份证是否已存在
        if(data_get($data,"type")==1){
            if(isset($data['company'])) unset($data['company']);
            $c_count = $this->getModel()->with([])->where("id_card","=",data_get($data,"id_card"))->count();
            if($c_count>0){
                throw new NoticeException("身份证已被占用");
            }
        }

        //如果时企业用户类型，则验证企业名称和企业税号是否存在
        if(data_get($data,"type")==2){
            if(isset($data['id_card'])) unset($data['id_card']);
            $company_model = new Company();
            $c_n_count = $company_model->with([])->where("name","=",data_get($data["company"],"name"))->count();
            if($c_n_count>0){
                throw new NoticeException("企业名称已存在");
            }
            $c_t_count = $company_model->with([])->where("tax_number","=",data_get($data["company"],"tax_number"))->count();
            if($c_t_count){
                throw new NoticeException("企业税号已存在");
            }

            //同步企业数据
        }

        //事务
        return DB::transaction(function() use($data){
            //添加User基础信息
            $create = $this->getModel()->with([])->create($data);
            if($create){
                //如果类型为企业类型则添加企业信息
                if(data_get($data,"type")==2) {
                    $c_model = new Company();
                    $company_info = data_get($data, "company");
                    $company_info["user_id"] = $create->id;
                    $c_model->with([])->create($company_info);
                }
                log_action($create, "添加客户：" . data_get($create, "link_name"), ActivityLog::MODULE_NAME_CUSTOMER);
                return SimpleResponse::success("添加成功");
            }
            return SimpleResponse::error("添加失败");
        });


    }

    /**
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request,$id)
    {
        $data = $this->validate($request,[
            "link_name"=>["required","string","max:255"],
            "mobile"=>new MobileRule(),
            //"region_id"=>["required","integer"],
            "address"=>["required","string","max:255"],
            "type"=>["required","integer","in:1,2"],
            "id_card"=>["required_without:company","required_if:type,1",new IdCardRule()],
            "company"=>["required_without:id_card","required_if:type,2"],
            "company.name"=>["required_with:company","string","max:255"],
            "company.tax_number"=>["required_with:company","regex:/^[A-Z0-9]{15}$|^[A-Z0-9]{17}$|^[A-Z0-9]{18}$|^[A-Z0-9]{20}$/"]
        ]);

        $user = Auth::user();
        if($user->type==2){
            $data['region_id'] =$user->region_id;
        }else{
            $this->validate($request,[
                "region_id"=>["required","integer"]
            ]);
            $data["region_id"] = $request->input("region_id");
        }

        //验证用户名是否存在

        //验证手机
        $m_count = $this->getModel()->with([])
                    ->where("id","<>",$id)
                    ->where("mobile","=",data_get($data,"mobile"))->count();
        if($m_count>0){
            throw new NoticeException("手机号已被占用");
        }

        $find = $this->getModel()->with([])->find($id);
        if($find){
            $old = clone $find;
            //如果时企业用户类型，则验证企业名称和企业税号是否存在
            if(data_get($data,"type")==2){
                if(isset($data['id_card'])) unset($data['id_card']);
                $company_model = new Company();
                $c_n_count = $company_model->with([])
                    ->where("user_id","<>",$find->id)
                    ->where("name","=",data_get($data["company"],"name"))->count();
                if($c_n_count>0){
                    throw new NoticeException("企业名称已存在");
                }
                $c_t_count = $company_model->with([])
                    ->where("user_id","<>",$find->id)
                    ->where("tax_number","=",data_get($data["company"],"tax_number"))->count();
                if($c_t_count){
                    throw new NoticeException("企业税号已存在");
                }
            }

            //事务
            return DB::transaction(function() use($data,$find,$old){
                //编辑User基础信息
                if($find->update($data)){
                    //如果类型为企业类型则添加企业信息
                    if(data_get($data,"type")==2) {
                        $c_model = new Company();
                        $company_info = data_get($data, "company");
                        $company_info["user_id"] = $find->id;
                        $find_company = $c_model->with([])->where("user_id","=",$find->id)->first();
                        if($find_company){
                            $find_company->update($company_info);
                        }else{
                            $c_model->with([])->create($company_info);
                        }
                    }
                    log_action($find, "编辑客户：" . data_get($find, "name"), ActivityLog::MODULE_NAME_CUSTOMER,$old);
                    return SimpleResponse::success("编辑成功");
                }
                return SimpleResponse::error("编辑失败");
            });

        }


    }

}

<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Rules\IdCardRule;
use App\Rules\MobileRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{


    /**
     * @param Request $request
     * @return User
     */
    public function info(Request $request) {
        /**
         * @var User $user
         */
        $user = $request->user();

        $user->load("wxUser");

        $user->load("company");

        return $user;
    }

    public function setMobile(Request $request)
    {
        $data = $this->validate($request,[
            "mobile"=>["required",new MobileRule()]
        ]);
        $user = $request->user();
        $user->mobile = data_get($data,"mobile");
        $user->save();
        return SimpleResponse::success("设置成功");

    }

    public function personalAuthenticate(Request $request)
    {
        $data = $this->validate($request,[
            "id_card"=>["required",new IdCardRule()],
            "link_name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "address"=>["required","string","max:255"]
        ]);

        //个人认证类型为1
        $data["type"] = 1;
        $user = Auth::user();

        //验证手机
        $m_count = User::with([])
            ->where("id","<>",$user->id)
            ->where("mobile","=",data_get($data,"mobile"))->count();
        if($m_count>0){
            throw new NoticeException("手机号已被占用");
        }

        $c_count = User::with([])
                    ->where("id_card","=",data_get($data,"id_card"))
                    ->where("id","<>",$user->id)->count();
        if($c_count>0){
            throw new NoticeException("身份证已被占用");
        }
        $u = User::with([])->find($user->id);
        if($u->update($data)){
            return SimpleResponse::success("认证成功");
        }
        throw new NoticeException("认证失败");

    }

    public function companyAuthenticate(Request $request)
    {
        $data = $this->validate($request,[
            "link_name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "company.name"=>["required","string","max:255"],
            "company.tax_number"=>["required","regex:/^[A-Z0-9]{15}$|^[A-Z0-9]{17}$|^[A-Z0-9]{18}$|^[A-Z0-9]{20}$/"],
            "company.address"=>["required","string","max:255"]
        ]);
        //企业认证类型为2
        $data['type']=2;
        $user = Auth::user();

        //验证手机
        $m_count = User::with([])
            ->where("id","<>",$user->id)
            ->where("mobile","=",data_get($data,"mobile"))->count();
        if($m_count>0){
            throw new NoticeException("手机号已被占用");
        }

        return DB::transaction(function ()use($data,$user){
            $u = User::with([])->find($user->id);
            $data["company"]["user_id"] = $user->id;
            if($u->update($data)){
                $company_model = new Company();
                //是否认证过企业信息
                $c = $company_model->with([])->where("user_id","=",$u->id)
                            ->count();
                if($c) throw new NoticeException("已认证过");

                $c_n_count = $company_model->with([])->where("name","=",data_get($data["company"],"name"))->count();
                if($c_n_count>0){
                    throw new NoticeException("企业名称已存在");
                }
                $c_t_count = $company_model->with([])->where("tax_number","=",data_get($data["company"],"tax_number"))->count();
                if($c_t_count){
                    throw new NoticeException("企业税号已存在");
                }

                $create = $company_model->with([])->create(data_get($data,"company"));
                if($create) return SimpleResponse::success("认证成功");
            }
        });



    }
}

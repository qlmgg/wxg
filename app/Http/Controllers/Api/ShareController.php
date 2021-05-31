<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Models\WxUser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Builder;

class ShareController extends SimpleController
{

    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new Share();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        return $this->query($request->input());
    }

    public function query(array $data):Builder
    {
        $user = Auth::user();
        $model = $this->getModel();
        $model = $model->with(['shareUser']);

        $model = $model->where("user_id","=",$user->id);
        return $model;
    }

    public function getShareCode()
    {
        return $this->createShareCode();
    }

    public function createShareCode()
    {
        $user = Auth::user();
        $expire_at = Carbon::now()->addMinutes(5)->toDateTimeString();
        $data = ['user_id'=>$user->id,"expire_at"=>$expire_at];
        $code = Crypt::encrypt($data);
        return ["code"=>$code];
        //return url("api/user/share-confirm/{$code}");
    }

    public function getShareUrl()
    {

        $code = $this->getShareCode();
        return url("api/user/share-confirm/{$code}");
    }


    public function toConfirm($code)
    {
        $data = Crypt::decrypt($code);
        $give_user = User::with([])->select(["name","mobile","avatar_url"])->find(data_get($data,"user_id"));
        $give_user['code'] = $code;
        return $give_user;
    }

    public function confirm(Request $request)
    {
        $code = $request->input("code");
        $data = Crypt::decrypt($code);

        //判断是否过期
        if(Carbon::now()->toDateTimeString()>data_get($data,"expire_at")){
            throw new NoticeException("链接已失效");
        }

        $share_user = Auth::user();
        $give_user = User::with([])->find(data_get($data,"user_id"));
        if(!$give_user) throw new NoticeException("无效操作");
        //预留判断 自己不能共享给自己
        if($give_user->id==$share_user->id){
            throw new NoticeException("不能绑定自己");
        }
        //判断是否已共享过
        $count = Share::with([])->where("user_id","=",data_get($data,"user_id"))
                                ->where("share_user_id","=",$share_user->id)->count();
        if($count>0) throw new NoticeException("已是共享成员");
        $share_info['user_id'] = $give_user->id;
        $share_info['share_user_id'] = $share_user->id;
        Share::with([])->create($share_info);

        return SimpleResponse::success("加入成功");
    }

    public function unbind($id)
    {
        $find = $this->getModel()->with([])->findOrFail($id);
        $find->delete();
        return SimpleResponse::success("解绑成功");
    }

    /**
     * 通过CODE获取用户分享的用户信息
     * @param Request $request
     * @return Builder|Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @throws \Illuminate\Validation\ValidationException
     */
    public function shareUserInfo(Request $request)
    {
        $datas = $this->validate($request,[
           "code" => ["required"]
        ]);
        $code = data_get($datas,"code");
        $data = Crypt::decrypt($code);
        $give_user = User::with([])->select(["name","mobile","avatar_url"])->find(data_get($data,"user_id"));
        $give_user['code'] = $code;
        $give_user['expire_at'] = data_get($data,"expire_at");
        return $give_user;
    }

}

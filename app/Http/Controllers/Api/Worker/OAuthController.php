<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Models\Worker;
use App\Models\WxUser;
use App\Models\WxWorker;
use App\Rules\MobileRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OAuthController extends Controller
{


    public function token(Request $request)
    {
        $data = $this->validate($request, [
            "code" => ["required", "string"],
            "nickname" => ["required", "string"],
            "avatar_url" => ["required", "string"],
            "gender" => ["required", "in:0,1,2"],
            "country" => ["nullable", "string"],
            "province" => ["nullable", "string"],
            "city" => ["nullable", "string"],
            "language" => ["nullable", "string"],
        ]);

        if (empty($data["avatar_url"])) {
            if ($avatarUrl = $request->input("avatarUrl")) {
                $data["avatar_url"] = $avatarUrl;
            }
        }

        if (empty($data["nickname"])) {
            if ($nickname = $request->input("nickName")) {
                $data["nickname"] = $nickname;
            }
        }

        $model = new WxWorker();

        // 性别 0 未知 1男 2女
        if (isset($data["gender"])) {
            $data["gender"] = $model->getGenderText($data["gender"]);
        }

        // 根据code 获取用户信息
        $app = get_worker_mini_program();
        $code = $data["code"];
        $result = $app->auth->session($code);
        //return $result;
        /*$result = [
            "session_key" => "x3CNgQe1UCqJBD5CysC1yA==",
            "expires_in" => 7200,
            "openid" => "ohLMD0cKqgxNbs9wJrApoJWCEMas",
            "unionid" => "oC_ZI1EqPNnShZtwqQDbe4BlDE3o",
        ];*/

        $data["openid"] = data_get($result, "openid");
        $data["unionid"] = data_get($result, "unionid");

        // 添加微信app_id
        $data['app_id'] = data_get($app->getConfig(), 'app_id');

        /**
         * @var WxWorker $wxUser
         */
        $wxUser = WxWorker::with([])->updateOrCreate(["openid" => $data["openid"]], $data);
        /**
         * @var Worker $user
         */
        $user = $wxUser->worker;
        if (empty($user)) {
            /*
            $user =  DB::transaction(function ()use ($wxUser) {

                $user = Worker::with([])->create([
                    "model_type" => get_class($wxUser),
                    "model_id" => $wxUser->id,
                    "username" => $wxUser->openid,
                    "name" => $wxUser->nickname,
                    "avatar_url" => $wxUser->avatar_url
                ]);

                $wxUser->worker_id = $user->id;
                $wxUser->save();
                return $user;
            });
            */
        }

        if ($user->name != $wxUser->nickname) {
            //$user->name = $wxUser->nickname;
            //$user->save();
        }

        $personalAccessTokenResult = $user->createToken("wx", ["wx"]);

        $token = $personalAccessTokenResult->token;
        $user->withAccessToken($token);
        $user->setWxSessionKey(data_get($result, "session_key"));

        return [
            "access_token" => $personalAccessTokenResult->accessToken,
            "token_type" => "Bearer",
            "res"=>$result,
            "data"=>$data
        ];
    }


    public function mtoken(Request $request)
    {
        $data = $this->validate($request, [
            "nickname" => ["required", "string"],
            "avatar_url" => ["nullable", "string"],
            "gender" => ["required", "in:0,1,2"],
            "country" => ["nullable", "string"],
            "province" => ["nullable", "string"],
            "city" => ["nullable", "string"],
            "language" => ["nullable", "string"],
        ]);

        if (empty($data["avatar_url"])) {
            if ($avatarUrl = $request->input("avatarUrl")) {
                $data["avatar_url"] = $avatarUrl;
            }
        }

        if (empty($data["nickname"])) {
            if ($nickname = $request->input("nickName")) {
                $data["nickname"] = $nickname;
            }
        }

        $model = new WxWorker();

        // 性别 0 未知 1男 2女
        if (isset($data["gender"])) {
            $data["gender"] = $model->getGenderText($data["gender"]);
        }
        /**
         * @var WxWorker $wxUser
         */
        $worker = $request->user();
        $model->updateOrCreate([
            "worker_id" => $worker->id
        ], $data);
        return SimpleResponse::success("更新成功");
    }


    /**
     * 用于测试的本地登录
     * @param Request $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function localLogin(Request $request)
    {
        $data = $this->validate($request, [
            "username" => ["required", "string"]
        ]);

        if (!in_array($request->ip(), config("auth.allow_local_login"))) {
            abort(403);
        }

        /**
         * @var Worker $user
         */
        $user = Worker::with([])->where("username", "=", $data["username"])->firstOrFail();

        $personalAccessTokenResult = $user->createToken("wx", ["wx"]);

        return [
            "access_token" => $personalAccessTokenResult->accessToken,
            "token_type" => "Bearer"
        ];
    }

    public function mobileLogin(Request $request)
    {
        $data = $this->validate($request, [
            "code" => ["required"],
            "iv" => ["required", "string"],
            "encryptedData" => ["required", "string"],
        ]);
        $code = data_get($data, "code");
        $app = get_worker_mini_program();

        $app_id = data_get($app->getConfig(), 'app_id');
        $result = $app->auth->session($code);

        if (!data_get($result, "session_key")) {
            throw new NoticeException(json_encode($result));
            //throw new NoticeException("数据解密失败");
        }

        $session = data_get($result, "session_key");
        $iv = data_get($data, "iv");
        $encryptedData = data_get($data, "encryptedData");
        $decryptData = $app->encryptor->decryptData($session, $iv, $encryptedData);
        $mobile = $decryptData["phoneNumber"];

        $worker = Worker::with([])->where("mobile", "=", $mobile)->first();
        if (!$worker) throw new NoticeException($mobile . "员工不存在");
        if ($worker->status == 0) throw new NoticeException("员工已禁用");

        //$data["openid"] = data_get($result, "openid");
        //根据worker_id查询WX
        $wxWorker = $worker->wxWorker;

        WxWorker::with([])->updateOrCreate([
            "openid" => data_get($result, "openid"),
        ], [
            "app_id" => $app_id,
            "mobile" => $mobile,
            "worker_id" => $worker->id,
            "unionid" => data_get($result, "unionid")

        ]);

        $personalAccessTokenResult = $worker->createToken("wx", ["wx"]);

        return [
            "access_token" => $personalAccessTokenResult->accessToken,
            "token_type" => "Bearer",
            "mobile" => $mobile,
            "res"=>$result
        ];

    }

    public function refreshSessionKey(Request $request)
    {
        $data = $request->validate($request, [
            "code" => ["required", "string"]
        ]);
        $app = get_mini_program();
        $code = $data["code"];
        $result = $app->auth->session($code);

        $user = $request->user();
        if (empty(data_get($result, "session_key"))) {
            throw new NoticeException("session_key 获取失败");
        }

        $user->setWxSessionKey(data_get($result, "session_key"));
        return SimpleResponse::success("刷新成功");
    }

}

<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SimpleResponse;
use App\Models\User;
use App\Models\WxUser;
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

        $model = new WxUser();

        // 性别 0 未知 1男 2女
        if (isset($data["gender"])) {
            $data["gender"] = $model->getGenderText($data["gender"]);
        }

        // 根据code 获取用户信息
        $app = get_mini_program();
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
         * @var WxUser $wxUser
         */
        $wxUser = WxUser::with([])->updateOrCreate(["openid" => $data["openid"]], $data);
        /**
         * @var User $user
         */
        $user = $wxUser->user;
        if (empty($user)) {
            $user =  DB::transaction(function ()use ($wxUser) {
                /**
                 * @var User $user
                 */
                $user = User::with([])->create([
                    "model_type" => get_class($wxUser),
                    "model_id" => $wxUser->id,
                    "username" => $wxUser->openid,
                    "name" => $wxUser->nickname,
                    "avatar_url" => $wxUser->avatar_url
                ]);

                $wxUser->user_id = $user->id;
                $wxUser->save();
                return $user;
            });
        }

        if ($user->name != $wxUser->nickname) {
            $user->name = $wxUser->nickname;
            $user->save();
        }

        $personalAccessTokenResult =  $user->createToken("wx", ["wx"]);

        $token = $personalAccessTokenResult->token;
        $user->withAccessToken($token);
        $user->setWxSessionKey(data_get($result, "session_key"));

        return [
            "access_token" => $personalAccessTokenResult->accessToken,
            "token_type" => "Bearer"
            //"op"=>data_get($result, "openid"),
        ];
    }


    /**
     * 用于测试的本地登录
     * @param Request $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function localLogin(Request $request) {
        $data = $this->validate($request, [
            "username" => ["required", "string"]
        ]);

        if (!in_array($request->ip(), config("auth.allow_local_login"))) {
            abort(403);
        }

        /**
         * @var User $user
         */
        $user = User::with([])->where("username", "=", $data["username"])->firstOrFail();

        $personalAccessTokenResult =  $user->createToken("wx", ["wx"]);

        return [
            "access_token" => $personalAccessTokenResult->accessToken,
            "token_type" => "Bearer"
        ];
    }


    public function refreshSessionKey(Request $request) {
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

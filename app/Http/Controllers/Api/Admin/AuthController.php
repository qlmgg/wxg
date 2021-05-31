<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SimpleResponse;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $data = $this->validate($request, [
            "username" => ["required"],
            "password" => ["required"],
        ]);


        $url = config("app.url") . "/oauth/token";

        $client = collect(config("passport.clients"))
            ->where("grand_type", "=", "password")
            ->where("provider", '=', "worker")
            ->first();

        if (empty($client)) {
            throw new NoticeException("请先配置password的clinet");
        }


        $data = [
            'grant_type' => 'password',
            'client_id' => data_get($client, "client_id"),
            'client_secret' => data_get($client, "client_secret"),
            'username' => data_get($data, "username"),
            'password' => data_get($data, "password"),
            'scope' => '',
        ];
        /**
         * @var Worker $worker
         */
        $worker = Worker::with([])->where("mobile", '=', $data["username"])->first();

        if (empty($worker)) {
            throw new NoticeException("密码账号错误");
        }

        if ($worker->status != 1) {
            throw new NoticeException("账号被禁用,请联系管理员");
        }

        $result = Http::post($url, $data);

        if (empty(data_get($result, "access_token"))) {
            throw new NoticeException("密码账号错误");
        }

        return $result;
    }


    public function userInfo(Request $request)
    {
        /**
         * @var Worker $user
         */
        $user = $request->user();

        $data = $user->toArray();

        // 获取用户的权限
        $permission = $user->getCacheMenuPermission();

        $data['permissions'] = $permission;

        return $data;
    }


    public function logout(Request $request)
    {
        /**
         * @var Worker $user
         */
        $user = $request->user();

        $token = $user->token();
        if ($token) {
            $token->revoke();
        }

        return SimpleResponse::success("退出成功");
    }
}

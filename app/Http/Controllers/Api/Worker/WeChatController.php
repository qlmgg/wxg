<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SimpleResponse;
use App\Models\User;
use Illuminate\Http\Request;

class WeChatController extends Controller
{

    public function refreshSessionKey(Request $request) {
        $data = $this->validate($request, [
            "code" => ["required", "string"]
        ]);
        $app = get_worker_mini_program();
        $code = data_get($data, "code");
        $result = $app->auth->session($code);

        /**
         * @var User $user
         */
        $user = $request->user();

        if (empty($user)) {
            throw new NoticeException("请先登录");
        }

        $user->setWxSessionKey(data_get($result, "session_key"));

        return SimpleResponse::success("刷新成功");
    }


    public function decryptData(Request $request) {

        $data = $this->validate($request, [
            "iv" => ["required", "string"],
            "encryptedData" => ["required", "string"],
        ]);

        /**
         * @var User $user
         */
        $user = $request->user();
        if (empty($user)) {
            throw new NoticeException("请先登录");
        }
        $app = get_worker_mini_program();


        $session = $user->getWxSessionKey();
        $iv = data_get($data, "iv");
        $encryptedData = data_get($data, "encryptedData");
        return $app->encryptor->decryptData($session, $iv, $encryptedData);
    }

}

<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SimpleResponse;
use App\Models\User;
use app\models\WUser;
use App\Models\WxOfficialUser;
use App\Models\WxUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WeChatController extends Controller
{

    public function refreshSessionKey(Request $request)
    {
        $data = $this->validate($request, [
            "code" => ["required", "string"]
        ]);
        $app = get_mini_program();
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


    public function decryptData(Request $request)
    {

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
        $app = get_mini_program();

        $session = $user->getWxSessionKey();
        $iv = data_get($data, "iv");
        $encryptedData = data_get($data, "encryptedData");
        return $app->encryptor->decryptData($session, $iv, $encryptedData);
    }


    // 接入公众号

    public function official()
    {
        $app = get_official_account();

        $app->server->push(function ($message) {
            switch ($message['MsgType']) {
                case 'event':
                    if (data_get($message, 'Event') == 'subscribe') { // 关注事件
                        return $this->subscribe($message);
                    }
                    break;
//                case 'text':
//                    return '收到文字消息';
            }
        });


        return $app->server->serve();

    }


    protected function subscribe($message)
    {
        $this->saveUser(data_get($message, 'FromUserName'), data_get($message, 'ToUserName'));
//        return json_encode($message);
        return "欢迎关注";
    }


    /**
     * 保存用户
     * @param $openid
     * @param $app_id
     * @return WxOfficialUser
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    protected function saveUser($openid, $app_id)
    {
        $app = get_official_account();

        $data = $app->user->get($openid);

        $data['app_id'] = $app_id;

        $data['groupid'] = $data['groupid'] ? $data['groupid'] : '';


        $data = WxOfficialUser::convertOrigin($data);
        /**
         * @var WxOfficialUser $user
         */
        $user = WxOfficialUser::with([])
            ->updateOrCreate([
                "app_id" => $data['app_id'],
                "openid" => $data['openid'],
            ], $data);

        return $user;
    }


    public function officeUserInfo()
    {
        $openid = "o3ue06Ksg8RWdvECIapAK2sOQPVE";

        $app = get_official_account();

        $data = $app->user->get($openid);

        return $data;
    }
}

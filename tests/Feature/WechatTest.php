<?php

namespace Tests\Feature;

use App\Jobs\UpdateOfficialUserJob;
use App\Models\Demand;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorkerAction;
use App\Models\User;
use App\Models\Worker;
use App\Models\WxOfficialUser;
use App\TemplateMessageSend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class WechatTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * @return \EasyWeChat\MiniProgram\Application
     */
    protected function getApp()
    {
        return get_mini_program();
    }


    public function testAccess()
    {
        $app = $this->getApp();


        $accessToken = $app->access_token;
        $token = $accessToken->getToken();

        dd($token);
    }

    public function testLogin()
    {

        $app = $this->getApp();

        $code = "083zQn0w3PHuCV2cN11w35ekHu0zQn0M";
        $result = $app->auth->session($code);

        dd($result);

        /*$result = [
            "session_key" => "x3CNgQe1UCqJBD5CysC1yA==",
            "expires_in" => 7200,
            "openid" => "ohLMD0cKqgxNbs9wJrApoJWCEMas",
            "unionid" => "oC_ZI1EqPNnShZtwqQDbe4BlDE3o",
        ];*/
    }


    public function testSendTemplateMessage() {
        $result = TemplateMessageSend::sendToUserTest();

        dd($result);
    }


    public function testGetOfficeAccount() {
        $app = get_official_account();
        $nextOpenId = null;
//        dd($app->user->list($nextOpenId));

        $user = $app->user->get('o3ue06Ksg8RWdvECIapAK2sOQPVE');

        dd($user);

        /**
         * array:17 [
            "subscribe" => 1
            "openid" => "o3ue06NzwLoFDqPeCaXbvNK9v1v0"
            "nickname" => "邓夏伍"
            "sex" => 1
            "language" => "zh_CN"
            "city" => "成都"
            "province" => "四川"
            "country" => "中国"
            "headimgurl" => "http://thirdwx.qlogo.cn/mmopen"
            "subscribe_time" => 1611136314
            "unionid" => "oC_ZI1EqPNnShZtwqQDbe4BlDE3o"
            "remark" => ""
            "groupid" => 0
            "tagid_list" => []
            "subscribe_scene" => "ADD_SCENE_QR_CODE"
            "qr_scene" => 0
            "qr_scene_str" => ""
        ]
         */


        $convertOrigin = WxOfficialUser::convertOrigin($user);

        $officialUser = null;
        if ($convertOrigin) {
            $officialUser = WxOfficialUser::with([])->updateOrCreate([
                "openid" => data_get($convertOrigin, 'openid')
            ], $convertOrigin);
        }

        dd($officialUser, $user);

    }

    public function testSendWorkSignOutToUser() {
        $user = User::with([])->findOrFail(2);

        $action = MonthCheckWorkerAction::with([])->findOrFail(1);
        $result = TemplateMessageSend::sendWorkSignOutToUser($user, $action);

        dd($result);
    }


    public function testWorkerMin()
    {
        dd(get_worker_mini_program()->access_token->getToken());
    }


    public function testUpdateOfficialUserJob() {
        dispatch_now(new \App\Jobs\UpdateOfficialUserJob());
    }


    public function testSendTemplateMessage2() {
        $worker = Worker::with([])->find(8);
        $action = MonthCheckWorkerAction::with([])->find(112);
        $result = TemplateMessageSend::sendWorkerSignInToWorker($worker, $action);

        dd($result);
    }

    public function testSendCreateFreeCheckOrderToUser()
    {

        $app = get_official_account();

        $app->access_token->refresh();

        $user = User::with([])->findOrFail(1);

        $month_check = MonthCheck::with([])->find(1);

        $worker = Worker::with([])->find(8);
        $action = MonthCheckWorkerAction::with([])->find(112);
        $worker_names = "小李，小明，小红";
        $result = TemplateMessageSend::SendCreateFreeCheckOrderToUser($user, $month_check,$worker_names);

        dd($result);
    }

    public function testSendDemandToRegionWorker()
    {
        $demand = Demand::with([])->find(1);
        $res = TemplateMessageSend::sendDemandToRegionWorker($demand);
        dd($res);
    }

}

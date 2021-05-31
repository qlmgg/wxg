<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Controller;
use App\Models\SimpleResponse;
use App\Models\Sms;
use App\Models\Worker;
use App\Notifications\SendWorkLoginCodeNotification;
use App\Rules\MobileRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

    protected $login_code_type = "work_login_code";

    /**
     * 员工登录发送验证码
     * @param Request $request
     * @return SimpleResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendLoginSms(Request $request)
    {
        $data = $this->validate($request, [
            "mobile" => ["required", new MobileRule()]
        ]);

        $phone = $data["mobile"];

        $workerCount = Worker::with([])->where("mobile", "=", $phone)->count();
        if (empty($workerCount)) {
            throw new NoticeException("当前员工不存在");
        }

        $type = $this->login_code_type;

        $sms = Sms::query()->where('phone', "=", $phone)
            ->where('type', $type)
            ->where('status', 0)
            ->orderBy('id', 'desc')
            ->first();
        //todo 过期或者null
        if (!$sms || (strtotime($sms->created_at) + $sms->expires_in < time())) {
            $code = rand(1000, 9999);
            /**
             * @var Sms $sms
             */
            $sms = Sms::with([])->create([
                'type' => $type,
                'expires_in' => 60,
                'phone' => $phone,
                'template_code' => config('dysms.templateCode'),
                'param' => ['code' => $code],
            ]);
            $sms->notify(new SendWorkLoginCodeNotification());
            return SimpleResponse::success("发送成功");
        } else {
            return SimpleResponse::error('发送过于频繁, 请稍后再试!');
        }
    }

    public function smsLogin(Request $request)
    {
        $data = $this->validate($request, [
            "mobile" => ["required", new MobileRule()],
            "code" => ['required', "min:4"]
        ]);

        $type = $this->login_code_type;
        $phone = $data["mobile"];
        // 获取 最新的验证码
        /**
         * @var Sms $sms
         */
        $sms = Sms::query()->where('phone', "=", $phone)
            ->where('type', $type)
            ->where('status', 0)
            ->orderBy('id', 'desc')
            ->first();

        if (empty($sms)) {
            throw new NoticeException("验证码不正确");
        }

        if ($data["code"] != $sms->getCode()) {
            throw new NoticeException("验证码不正确");
        }

        /**
         * @var Worker $work
         */
        $work = Worker::with([])->where("mobile", "=", $data["mobile"])->firstOrFail();

        $personalAccessTokenResult = DB::transaction(function ()use ($work, $sms) {
            $personalAccessTokenResult = $work->createToken("wx", ["wx"]);

            $sms->status = 1;
            $sms->save();

            return $personalAccessTokenResult;
        });


        // 将验证改成已使用
        return [
            "access_token" => $personalAccessTokenResult->accessToken,
            "token_type" => "Bearer"
        ];
    }


    public function passwordLogin(Request $request)
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

        return Http::post($url, $data);
    }
}

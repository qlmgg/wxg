<?php


namespace App;


use App\Models\CheckOrder;
use App\Models\Comment;
use App\Models\ContractManagement;
use App\Models\Demand;
use App\Models\Message;
use App\Models\MonthCheck;
use App\Models\MonthCheckWorker;
use App\Models\MonthCheckWorkerAction;
use App\Models\PaymentManagement;
use App\Models\PushRecords;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;

class TemplateMessageSend
{

    /**
     * 获取公众号
     * @return \EasyWeChat\OfficialAccount\Application
     */
    protected static function getUserApp()
    {
        return get_official_account();
    }

    /**
     * 接单成功推送信息
     * @param Worker $worker
     * @param MonthCheckWorkerAction $month_check_worker_action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendAcceptOrderSuccessToWorker(Worker $worker,MonthCheckWorkerAction $month_check_worker_action,$type)
    {
        //通过 month_check_worker 获取check_order的地址
        $check_order = CheckOrder::with(['region'])->find($month_check_worker_action->check_order_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        if($type==1){
            $title = "恭喜您，您有一个订单接单成功！";
        }else{
            $title = "恭喜您，您有一个订单抢单成功！";
        }
        /**
         *{{first.DATA}}
         *订单编号：{{keyword1.DATA}}
         *订单时间：{{keyword2.DATA}}
         *订单内容：{{keyword3.DATA}}
         *订单地址：{{keyword4.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => 'MazPnzPCzxtb_nYHDDsQxfZXnVv5QEiid6KH9pxHqXc',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id')
                    //"pagepath"=>"index"
                ],
                'data' => [
                    "first" => $title,
                    "keyword1" =>$check_order->order_code, // 订单编号
                    "keyword2" => $time, // 时间
                    "keyword3" => "月检", // 内容
                    "keyword4" => $check_order->address, // 类型
                    "remark" => "请尽快与甲方取得联系，确认相关事宜，谢谢！",
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $worker->id,
            "from_type" => get_class($month_check_worker_action),
            "from_id" => $month_check_worker_action->id,
            "title" => "接单成功",
            "content" => "员工".$worker->name."于".$time." 接单, 如有疑问，请与平台进行联系。",
            "type" => 2
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

    /**
     * 平台派单消息推送
     * @param Worker $worker
     * @param MonthCheckWorkerAction $month_check_worker_action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendOrderToWorker(Worker $worker,MonthCheckWorkerAction $month_check_worker_action)
    {
        //通过 month_check_worker 获取check_order的地址
        $check_order = CheckOrder::with(['region'])->find($month_check_worker_action->check_order_id);
        $month_check = MonthCheck::with([])->find($month_check_worker_action->month_check_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        /**
         * {{first.DATA}}
         *客户信息：{{keyword1.DATA}}
         *上门时间：{{keyword2.DATA}}
         *上门地址：{{keyword3.DATA}}
         *服务类型：{{keyword4.DATA}}
         *备注信息：{{keyword5.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => 'Nu865wVsqb4zfkTvNCrCnIPq3B9H9KSiNrHy0iaP3fA',
                "miniprogram"=>[
                        "appid"=>config('wechat.mini_program.worker.app_id'),
                        "pagepath"=>"pages/order-center/order-center"
                ],
                'data' => [
                    "first" => "您有新的派单通知！", // 详细内容
                    "keyword1" => "姓名：".$check_order->name.",联系号码：".$check_order->mobile, // 客户信息
                    "keyword2" => $month_check->door_time, // 时间
                    "keyword3" => $check_order->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "麻烦尽早上门，谢谢。请按照上门时间尽快到达！", // 备注
                    "remark" => "",
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $worker->id,
            "from_type" => get_class($month_check_worker_action),
            "from_id" => $month_check_worker_action->id,
            "title" => "派单",
            "content" => "系统于".$time." 派单, 如有疑问，请与平台进行联系。",
            "type" => 2
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

    /**
     * 员工签到推送消息给用户
     * @param User $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignInToUser(User $user,MonthCheckWorkerAction $action)
    {
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */

        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "唯修工月检工人已签到", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "工人已到场", // 备注
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签到",
            "content" => "员工".$worker->name." 于 ".$time." 签到, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 1
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    /**
     * 员工签退推送消息给用户
     * @param User $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignOutToUser(User $user, MonthCheckWorkerAction $action)
    {
        //
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();

        // todo  通过 $user 获取openid
        $time = now()->format("Y-m-d H:i:s");

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "唯修工月检工人已签退", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "工人已离场", // 备注
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签退",
            "content" => "员工".$worker->name." 于 ".$time." 签退, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 1
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

    /**
     * 员工签退推送消息给员工
     * @param Worker $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignOutToWorker(Worker $user, MonthCheckWorkerAction $action)
    {
        //
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();

        // todo  通过 $user 获取openid
        $time = now()->format("Y-m-d H:i:s");

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "您已签退成功", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $user->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签退",
            "content" => "员工".$worker->name." 于 ".$time." 签退, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

    /**
     * 员工签到推送消息给员工
     * @param Worker $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignInToWorker(Worker $user,MonthCheckWorkerAction $action)
    {
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06G1jR47vD4givTLK-NxTLLU";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "您已签到成功", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "完工后请及时签退！", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $user->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签到",
            "content" => "员工".$worker->name." 于 ".$time." 签到, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 1
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    //---------------------------------员工端模板消息-----------------------------------------------------------

    /**
     * 创建免费月检订单的时候推送消息给客户
     * @param User $user
     * @param $month_check
     * @param $woker_names
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendCreateFreeCheckOrderToUser(User $user,$month_check,$woker_names)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        /**
         * {{first.DATA}}
         * 服务项目：{{keyword1.DATA}}
         * 服务专员：{{keyword2.DATA}}
         * 服务时间：{{keyword3.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"Nu865wVsqb4zfkTvNCrCnIPq3B9H9KSiNrHy0iaP3fA",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.app_id'),
                    "pagepath"=>"pages/demond-list/demond-list"
                ],
                'data' => [
                    "first" => "尊敬的用户你好，你的免费检查订单已成功创建，请点击查看", // 详细内容
                    "keyword1" => "检查", // 员工
                    "keyword2" => $woker_names, // 时间
                    "keyword3" => $month_check->door_time, // 地点
                    "remark" => "为您提供上门服务", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($month_check),
            "from_id" => $month_check->id,
            "title" => "创建免费订单",
            "content" => "为".$user->name." 于 ".$time." 创建免费订单, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    /**
     * 本次月检订单已完成
     * @param User $user
     * @param $month_check
     * @param $woker_names
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendEndMonthCheckToUser(User $user,$month_check,$woker_names)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        //查询订单
        $check_order = CheckOrder::with([])->find($month_check->check_order_id);
        $address = "";
        if($check_order){
            $address=$check_order->address;
        }
        /**
         * {{first.DATA}}
         * 服务项目：{{keyword1.DATA}}
         * 服务专员：{{keyword2.DATA}}
         * 服务时间：{{keyword3.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.app_id'),
                    "pagepath"=>"pages/month-order-detail/month-order-detail?id=".$month_check->check_order_id
                ],
                'data' => [
                    "first" => "尊敬的用户你好，你的月检订单已结束，请点击查看", // 详细内容
                    "keyword1" => $woker_names, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $address, // 地点
                    "keyword4" => "检查", // 类型
                    "remark" => "订单已完成", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($month_check),
            "from_id" => $month_check->id,
            "title" => "月检订单已结束",
            "content" => $user->name." 于 ".$time." 月检订单结束, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    public static function sendEndCheckOrderToUser(User $user,CheckOrder $order)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$order->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         *{{first.DATA}}
         *服务人员：{{keyword1.DATA}}
         *服务时间：{{keyword2.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"Fb2bt7RPuECiTQSJx3s5X04KJ_dpavTWACaTaJ6yyUc",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.default.app_id'),
                    "pagepath"=>"pages/user-center/user-center"
                ],
                'data' => [
                    "first" => "尊敬的用户您好，您的合同订单已结束，请点击查看：", // 详细内容
                    "keyword1" => $region_worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "remark" => "", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($order),
            "from_id" => $order->id,
            "title" => "月检订单已结束",
            "content" => $user->name." 订单于 ".$time." 结束, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    // 支付到期提示
    public static function sendEndPayDataToUser(User $user,PaymentManagement $paymentManagement, $str)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //操作人员
        // $region_worker = Worker::with([])->where("worker_id","=",$paymentManagement->worker_id)->first();
        $region_worker = Worker::with([])->find(data_get($paymentManagement, "worker_id"));

        /**
         *{{first.DATA}}
         *服务人员：{{keyword1.DATA}}
         *服务时间：{{keyword2.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"Fb2bt7RPuECiTQSJx3s5X04KJ_dpavTWACaTaJ6yyUc",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.default.app_id'),
                    "pagepath"=>"pages/month-order/month-order"
                ],
                'data' => [
                    "first" => "尊敬的用户您好，您的付款周期".$str."，请点击查看：", // 详细内容
                    "keyword1" => $region_worker->name, // 员工
                    "keyword2" => $paymentManagement->date_payable, // 时间
                    "remark" => "", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($paymentManagement),
            "from_id" => $paymentManagement->id,
            "title" => "付款周期". $str,
            "content" => $user->name." 您在 ".$paymentManagement->date_payable." 有一笔应付款, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }
    // 支付到期提示
    public static function sendEndPayDataToRegionWorker(CheckOrder $checkOrder,PaymentManagement $paymentManagement, $str)
    {
        
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",data_get($checkOrder, "region_id"))->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         * {{first.DATA}}
         * 订单编号：{{keyword1.DATA}}
         * 施工地址：{{keyword2.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        // $officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"P-WPci6nC4qrU85yljjxA1fBMJr6eDPcbumrb8tu-Os",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/month-order/month-order"
                ],
                'data' => [
                    "first" => "尊敬的".$region_worker->name."经理", // 详细内容
                    "keyword1" => data_get($checkOrder, "order_code"), // 订单编号
                    "keyword2" => data_get($checkOrder, "address"), // 施工地址
                    "remark" => data_get($checkOrder, "enterprise_name") . "，该单位支付计划".$str."，请您及时跟进合同进度。", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($paymentManagement),
            "from_id" => $paymentManagement->id,
            "title" => "尊敬的".$region_worker->name."经理",
            "content" => data_get($checkOrder, "enterprise_name") . "，该单位支付计划".$str."，请您及时跟进合同进度。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    // 意见订单反馈推送
    public static function sendFeedbackToUser(PushRecords $pushRecords)
    {
        if (data_get($pushRecords, "type") == 1) {
            $workers = Worker::with([])
                ->where("status", "=", 1)
                ->get();
        } else {
            $workers = Worker::with([])
                ->where("status", "=", 1)
                ->where("region_id", "=", data_get($pushRecords, "region_id"))
                ->get();
        }

        $message = [];

        $comment = Comment::with([])->find(data_get($pushRecords, 'comment_id'));

        foreach ($workers as $value) {
            $message[] = Message::with([])->create([
                "to_worker_id" => $value->id,
                "from_type" => get_class($comment),
                "from_id" => $comment->id,
                "title" => "意见反馈推送",
                "content" => "你有一条新的意见反馈，请查收！",
                "type" => 1,
                "can_confirm" => 0
            ]);
        }

        return [
            "message" => $message
        ];

    }

    //-------------------------------------------------区域经理端模板消息-----------------------------------------

    /**
     * 用户提交需求推送给区域经理
     * @param Demand $demand
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendDemandToRegionWorker(Demand $demand)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$demand->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         *{{first.DATA}}
         *客户姓名：{{keyword1.DATA}}
         *客户手机：{{keyword2.DATA}}
         *提交时间：{{keyword3.DATA}}
         *表单所有项目：{{keyword4.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"7BEoVvoxHg8dKVKZsXaEpTf0zya7IpstNfXJmAKDWCg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/demond-list/demond-list"
                ],
                'data' => [
                    "first" => "有新的客户提交需求单，请及时确认", // 详细内容
                    "keyword1" => $demand->name, // 姓名
                    "keyword2" => $demand->mobile, // 手机
                    "keyword3" => $time, // 时间
                    "keyword4" => "", // 需求内容
                    "remark" => "", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($demand),
            "from_id" => $demand->id,
            "title" => "客户提交需求单",
            "content" => $demand->name." 于 ".$time." 提交需求, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    public static function sendWorkerChangeStatusToRegionWorker(Worker $worker,$status)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$worker->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();
        $type = "";
        if($status==0){
            $type = "休息";
        }else if($status==1){
            $type = "空闲";
        }else if($status==2){
            $type = "工作中";
        }
        /**
         *{{first.DATA}}
         *状态类型：{{keyword1.DATA}}
         *操作用户：{{keyword2.DATA}}
         *操作时间：{{keyword3.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($worker),
            "from_id" => $worker->id,
            "title" => "工人工作状态改变",
            "content" => $worker->name." 于 ".$time." 状态改为".$type.", 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"ihYLqJwL-Nxo6f88jYSoejaxVODYcJs3je-RAN6CujM",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-detail/news-detail?id=".$message->id
                ],
                'data' => [
                    "first" => "您好，员工工作状态发生变更", // 详细内容
                    "keyword1" => $type,
                    "keyword2" => $worker->name, // 手机
                    "keyword3" => $time, // 时间
                    "remark" => "", //
                ],
            ]);
        }



        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    public static function sendOpinionToRegionWorker(User $user,Comment $opinion)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //用户如果没有提交过需求就不推送
        $demand = Demand::with([])->where("user_id","=",$user->id)->first();
        if(!$demand) return false;
        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$demand->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();
        /**
         *{{first.DATA}}
         *联系人：{{keyword1.DATA}}
         *联系电话：{{keyword2.DATA}}
         *反馈时间：{{keyword3.DATA}}
         *反馈内容：{{keyword4.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($opinion),
            "from_id" => $opinion->id,
            "title" => "你有一条新的意见反馈，请查收！",
            "content" => $opinion->name." 于 ".$time." 提交意见反馈,请及时查看 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"eENDUb7RdsMQL-LKPaasYHjw55-JB1GA0AoP6uWQcKU",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-detail/news-detail?id=".$message->id
                ],
                'data' => [
                    "first" => "你有一条新的意见反馈，请查收！", // 详细内容
                    "keyword1" => $opinion->name,
                    "keyword2" => $opinion->mobile, // 手机
                    "keyword3" => $time, // 时间
                    "keyword4" => $opinion->content, // 内容
                    "remark" => "", //
                ],
            ]);
        }

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    // 创建月检合同订单推荐模板消息
    public static function sendCreateCheckOrderToUser(User $user,CheckOrder $order)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$order->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         *{{first.DATA}}
         *服务人员：{{keyword1.DATA}}
         *服务时间：{{keyword2.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"Fb2bt7RPuECiTQSJx3s5X04KJ_dpavTWACaTaJ6yyUc",
                'data' => [
                    "first" => "尊敬的用户您好，您的合同订单已生成，请点击查看：", // 详细内容
                    "keyword1" => $region_worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "remark" => "", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($order),
            "from_id" => $order->id,
            "title" => "创建合同订单",
            "content" => $user->name." 订单于 ".$time." 创建, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    // 创建月检合同订单推荐模板消息
    public static function sendCreateMonthCheckOrderToUser(User $user,CheckOrder $order)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$order->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         * {{first.DATA}}
         * 服务项目：{{keyword1.DATA}}
         * 服务专员：{{keyword2.DATA}}
         * 服务时间：{{keyword3.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"Nu865wVsqb4zfkTvNCrCnIPq3B9H9KSiNrHy0iaP3fA",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.default.app_id'),
                    "pagepath"=>"pages/month-order-detail/month-order-detail?id=".data_get($order, "id")
                ],
                'data' => [
                    "first" => "尊敬的用户你好，你的月检订单已成功创建，请点击查看：", // 详细内容
                    "keyword1" => "检查", // 员工
                    "keyword2" => $region_worker->name, // 员工
                    "keyword3" => $time, // 时间
                    "remark" => "为您提供上门服务", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $user->id,
            "from_type" => get_class($order),
            "from_id" => $order->id,
            "title" => "创建月检合同订单",
            "content" => $user->name." 订单于 ".$time." 创建, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    // 合同到期推送模板消息
    public static function sendEndContractToUser(ContractManagement $contractManagement)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",data_get($contractManagement, "region_id"))->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        // DB::table("contract_management_log")->insert(["type"=>1, "message"=>$region_worker->name."[".$region_worker->id."]"]);

        /**
         * {{first.DATA}}
         * 合同编号：{{keyword1.DATA}}
         * 合同开始日期：{{keyword2.DATA}}
         * 合同结束日期：{{keyword3.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        // $officialOpenId = "o3ue06DmiCYwYUSX4o6YwYBzTehQ"; // 邱莎
        // $officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($contractManagement),
            "from_id" => $contractManagement->id,
            "title" => "合同到期消息推送",
            "content" => "单位名称：".$contractManagement->checkOrder->enterprise_name."，订单编号：".$contractManagement->checkOrder->order_code." 合同将于 ".$contractManagement->end_date." 过期, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"ma-IdzHv3dG2vaMt4t4oZCIDobRXQrKRpBJAgt7ToT8",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-detail/news-detail?id=" . data_get($message, "id")
                ],
                'data' => [
                    "first" => "尊敬的".$region_worker->name."经理：", // 详细内容
                    "keyword1" => $contractManagement->checkOrder->order_code, // 合同编号
                    "keyword2" => $contractManagement->signature_date, // 合同开始日期
                    "keyword3" => $contractManagement->end_date, // 合同结束日期
                    "remark" => "您跟进的甲方合同即将到期，请尽快与甲方取得联系，续签合同。", //
                ],
            ]);
        }

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    //------------------------------------------新增--------------------------------------------------
    //3：到场签到（签到人，签到地点，签到时间） 跳转到消息中心
    //4：签退信息（签退人，签退地点，签退时间） 跳转到消息中心
    //5：月检订单结束消息（用工地址，开始时间，结束时间，用工时长，结算金额）
    //6：接单成功推送消息（用工时间，用工地址）
    //7：抢单成功推送消息（用工时间，用工地址
    /**
     * 接单/抢单成功推送信息
     * @param Worker $worker
     * @param MonthCheckWorkerAction $month_check_worker_action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendAcceptOrderSuccessToRegionWorker(Worker $worker,MonthCheckWorkerAction $month_check_worker_action,$type)
    {
        //通过 month_check_worker 获取check_order的地址
        $check_order = CheckOrder::with(['region'])->find($month_check_worker_action->check_order_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        if($type==1){
            $title = "员工".$worker->name."接单成功！";
        }else{
            $title = "员工".$worker->name."抢单成功！";
        }

        $region_worker = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$worker->region_id)
            ->where("status","=",1)
            ->first();
        if(empty($region_worker)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        /**
         *{{first.DATA}}
         *订单编号：{{keyword1.DATA}}
         *订单时间：{{keyword2.DATA}}
         *订单内容：{{keyword3.DATA}}
         *订单地址：{{keyword4.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => 'MazPnzPCzxtb_nYHDDsQxfZXnVv5QEiid6KH9pxHqXc',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id')
                    //"pagepath"=>"index"
                ],
                'data' => [
                    "first" => $title,
                    "keyword1" =>$check_order->order_code, // 订单编号
                    "keyword2" => $time, // 时间
                    "keyword3" => "月检", // 内容
                    "keyword4" => $check_order->address, // 类型
                    "remark" => "请尽快与甲方取得联系，确认相关事宜，谢谢！",
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($month_check_worker_action),
            "from_id" => $month_check_worker_action->id,
            "title" => $title,
            "content" => "员工".$worker->name."于".$time." 接单, 如有疑问，请与平台进行联系。",
            "type" => 2
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

    /**
     * 接单/抢单成功推送信息
     * @param Worker $worker
     * @param MonthCheckWorkerAction $month_check_worker_action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendAcceptOrderSuccessToRegionWorkers(Worker $worker,MonthCheckWorkerAction $month_check_worker_action,$type,$region_worker)
    {
        //通过 month_check_worker 获取check_order的地址
        $check_order = CheckOrder::with(['region'])->find($month_check_worker_action->check_order_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        if($type==1){
            $title = "员工".$worker->name."接单成功！";
        }else{
            $title = "员工".$worker->name."抢单成功！";
        }

        /**
         *{{first.DATA}}
         *订单编号：{{keyword1.DATA}}
         *订单时间：{{keyword2.DATA}}
         *订单内容：{{keyword3.DATA}}
         *订单地址：{{keyword4.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => 'MazPnzPCzxtb_nYHDDsQxfZXnVv5QEiid6KH9pxHqXc',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id')
                    //"pagepath"=>"index"
                ],
                'data' => [
                    "first" => $title,
                    "keyword1" =>$check_order->order_code, // 订单编号
                    "keyword2" => $time, // 时间
                    "keyword3" => "月检", // 内容
                    "keyword4" => $check_order->address, // 类型
                    "remark" => "请尽快与甲方取得联系，确认相关事宜，谢谢！",
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($month_check_worker_action),
            "from_id" => $month_check_worker_action->id,
            "title" => $title,
            "content" => "员工".$worker->name."于".$time." 接单, 如有疑问，请与平台进行联系。",
            "type" => 2
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }


    /**
     * 员工签到推送消息给区域经理
     * @param Worker $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignInToRegionWorker(Worker $user,MonthCheckWorkerAction $action)
    {
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        $region_worker = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$worker->region_id)
            ->where("status","=",1)
            ->first();
        if(empty($region_worker)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06G1jR47vD4givTLK-NxTLLU";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "员工:".$worker->name."已签到成功", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "完工后请及时签退！", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签到",
            "content" => "员工".$worker->name." 于 ".$time." 签到, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 1
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    /**
     * 员工签到推送消息给区域经理
     * @param Worker $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignInToRegionWorkers(Worker $user,MonthCheckWorkerAction $action,$region_worker)
    {
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06G1jR47vD4givTLK-NxTLLU";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "员工:".$worker->name."已签到成功", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "完工后请及时签退！", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签到",
            "content" => "员工".$worker->name." 于 ".$time." 签到, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 1
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    /**
     * 员工签退推送消息给区域经理
     * @param Worker $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignOutToRegionWorker(Worker $user, MonthCheckWorkerAction $action)
    {
        //
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();

        // todo  通过 $user 获取openid
        $time = now()->format("Y-m-d H:i:s");

        $region_worker = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$worker->region_id)
            ->where("status","=",1)
            ->first();
        if(empty($region_worker)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "员工：".$worker->name."签退成功", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签退",
            "content" => "员工".$worker->name." 于 ".$time." 签退, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }

    /**
     * 员工签退推送消息给区域经理
     * @param Worker $user
     * @param MonthCheckWorkerAction $action
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendWorkerSignOutToRegionWorkers(Worker $user, MonthCheckWorkerAction $action,$region_worker)
    {
        //
        $worker = Worker::with([])->find($action->worker_id);
        $app = self::getUserApp();

        // todo  通过 $user 获取openid
        $time = now()->format("Y-m-d H:i:s");

        /**
         * 详细内容{{first.DATA}}
         * 员工：{{keyword1.DATA}}
         * 时间：{{keyword2.DATA}}
         * 地点：{{keyword3.DATA}}
         * 类型：{{keyword4.DATA}}
         * 备注：{{keyword5.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/news-center/news-center"
                ],
                'data' => [
                    "first" => "员工：".$worker->name."签退成功", // 详细内容
                    "keyword1" => $worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $action->address, // 地点
                    "keyword4" => "月检", // 类型
                    "keyword5" => "", // 备注
                    "remark" => "", // 类型
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($action),
            "from_id" => $action->id,
            "title" => "员工签退",
            "content" => "员工".$worker->name." 于 ".$time." 签退, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];
    }


    /**
     * 本次月检订单已完成
     * @param User $user
     * @param $month_check
     * @param $woker_names
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendEndMonthCheckToRegionWorker(User $user,$month_check,$woker_names)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        $region_worker = Worker::with([])
            ->where("type","=",2)
            ->where("region_id","=",$user->region_id)
            ->where("status","=",1)
            ->first();
        if(empty($region_worker)) {
            return [
                "message" => "区域经理不存在",
                "template_result" => []
            ];
        }

        /**
         * {{first.DATA}}
         * 服务项目：{{keyword1.DATA}}
         * 服务专员：{{keyword2.DATA}}
         * 服务时间：{{keyword3.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $user->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.app_id'),
                    //"pagepath"=>"pages/month-order-detail/month-order-detail?id=".$month_check->check_order_id
                ],
                'data' => [
                    "first" => "用户：".$user->name."的月检订单已结束，请点击查看", // 详细内容
                    "keyword1" => "检查", // 员工
                    "keyword2" => $woker_names, // 时间
                    "keyword3" => $time, // 地点
                    "remark" => "订单已完成", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_user_id" => $region_worker->id,
            "from_type" => get_class($month_check),
            "from_id" => $month_check->id,
            "title" => "月检订单已结束",
            "content" => $user->name." 于 ".$time." 月检订单结束, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    public static function sendEndCheckOrderToRegionWorker(User $user,CheckOrder $order)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        $region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$order->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         *{{first.DATA}}
         *服务人员：{{keyword1.DATA}}
         *服务时间：{{keyword2.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"Fb2bt7RPuECiTQSJx3s5X04KJ_dpavTWACaTaJ6yyUc",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.default.app_id'),
                    //"pagepath"=>"pages/user-center/user-center"
                ],
                'data' => [
                    "first" => "用户".$user->name."的合同订单已结束，请点击查看：", // 详细内容
                    "keyword1" => $region_worker->name, // 员工
                    "keyword2" => $time, // 时间
                    "remark" => "", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($order),
            "from_id" => $order->id,
            "title" => "月检订单已结束",
            "content" => $user->name." 订单于 ".$time." 结束, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    /**
     * 本次月检订单已完成
     * @param User $user
     * @param $month_check
     * @param $woker_names
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendEndMonthCheckToRegionWorkers(User $user,$month_check,$woker_names,$region_worker)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");
        //查询订单
        $check_order = CheckOrder::with([])->find($month_check->check_order_id);
        $address = "";
        if($check_order){
            $address=$check_order->address;
        }
        /**
         * {{first.DATA}}
         * 服务项目：{{keyword1.DATA}}
         * 服务专员：{{keyword2.DATA}}
         * 服务时间：{{keyword3.DATA}}
         * {{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                //'template_id' => '6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg',
                'template_id' =>"6IMPh9W1dueUxJuiyqhVQ2-xxLhgYdAbQSKcHB9AMeg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.app_id'),
                    //"pagepath"=>"pages/month-order-detail/month-order-detail?id=".$month_check->check_order_id
                ],
                'data' => [
                    "first" => "用户".$user->name."的月检订单已结束，请点击查看", // 详细内容
                    "keyword1" => $woker_names, // 员工
                    "keyword2" => $time, // 时间
                    "keyword3" => $address, // 地点,
                    "keyword4" => "检查", // 类型,
                    "remark" => "订单已完成", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($month_check),
            "from_id" => $month_check->id,
            "title" => "月检订单已结束",
            "content" => $user->name." 于 ".$time." 月检订单结束, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

    /**
     * 用户提交需求推送给区域经理
     * @param Demand $demand
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendDemandToRegionWorkers(Demand $demand,$region_worker)
    {
        $app = self::getUserApp();
        $time = now()->format("Y-m-d H:i:s");

        //选个区域经理
        //$region_worker = Worker::with([])->where("type","=",2)->where("region_id","=",$demand->region_id)->first();
        if(!$region_worker) $region_worker = Worker::with([])->where("type","=",1)->first();

        /**
         *{{first.DATA}}
         *客户姓名：{{keyword1.DATA}}
         *客户手机：{{keyword2.DATA}}
         *提交时间：{{keyword3.DATA}}
         *表单所有项目：{{keyword4.DATA}}
         *{{remark.DATA}}
         */
        $officialOpenId = $region_worker->getOfficialOpenId();
        //$officialOpenId = "o3ue06KHoD5VThrrR9nYtkH88cnY";
        //$officialOpenId = "o3ue06L58bUybDsMc6qIfd3wCwyI"; //吴
        $template_result = [];
        if ($officialOpenId) {
            $template_result = $app->template_message->send([
                'touser' => $officialOpenId,
                'template_id' =>"7BEoVvoxHg8dKVKZsXaEpTf0zya7IpstNfXJmAKDWCg",
                "miniprogram"=>[
                    "appid"=>config('wechat.mini_program.worker.app_id'),
                    "pagepath"=>"pages/demond-list/demond-list"
                ],
                'data' => [
                    "first" => "有新的客户提交需求单，请及时确认", // 详细内容
                    "keyword1" => $demand->name, // 姓名
                    "keyword2" => $demand->mobile, // 手机
                    "keyword3" => $time, // 时间
                    "keyword4" => "", // 需求内容
                    "remark" => "", //
                ],
            ]);
        }

        // 本系统的消息通知
        $message =  Message::with([])->create([
            "to_worker_id" => $region_worker->id,
            "from_type" => get_class($demand),
            "from_id" => $demand->id,
            "title" => "客户提交需求单",
            "content" => $demand->name." 于 ".$time." 提交需求, 如有疑问，请与平台进行联系。",
            "type" => 2,
            "can_confirm" => 0
        ]);

        return [
            "message" => $message,
            "template_result" => $template_result
        ];

    }

}

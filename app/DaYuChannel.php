<?php


namespace App;


use App\Models\Sms;
use Flc\Dysms\Client;
use Illuminate\Notifications\Notification;

class DaYuChannel
{
    /**
     * 发送指定的通知.
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send(Sms $notifiable, Notification $notification)
    {
        $message = $notification->toDaYu($notifiable);

        $config = [
            'accessKeyId' => config('dysms.accessKeyId'),
            'accessKeySecret' => config('dysms.accessKeySecret'),
        ];

        $client = new Client($config);


        $res = $client->execute($message);
        $notifiable->update(['result' => $res]);


//        if (config('app.env') == 'production') { // 正式环境发送
//            $res = $client->execute($message);
//            $notifiable->update(['result' => $res]);
//        } else {
//            $res = ["result" => "测试发送成功"];
////            $res = $client->execute($message);
//            $notifiable->update(['result' => $res]);
//        }
    }
}

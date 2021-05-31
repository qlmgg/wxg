<?php

namespace App\Jobs;

use App\Models\CheckOrder;
use App\Models\PaymentManagement;
use App\Models\TimedTaskLogos;
use App\Models\User;
use App\TemplateMessageSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckPayDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 即将到期
        $list1 = PaymentManagement::with([])
            ->whereBetween("date_payable", [Carbon::today()->startOfWeek(), Carbon::today()->endOfWeek()])
            ->where("pay_type", "=", 0)
            ->where("is_process", "=", 0)
            ->get();
        $list1->each(
            function ($v1) {
                $v1->is_process = 1;
                $v1->save();
                
                $user = User::with([])->find(data_get($v1, "user_id"));
                $checkOrder = CheckOrder::with([])->find(data_get($v1, "check_order_id"));
                TemplateMessageSend::sendEndPayDataToUser($user, $v1, "即将到期");
                TemplateMessageSend::sendEndPayDataToRegionWorker($checkOrder, $v1, "即将到期");
            }
        );
        
        // 已到期
        $list2 = PaymentManagement::with([])
            ->where("date_payable", "<", Carbon::today())
            ->where("pay_type", "=", 0)
            ->where("is_process", "<", 2)
            ->get();
        $list2->each(
            function ($v2) {
                $v2->is_process = 2;
                $v2->save();

                $user = User::with([])->find(data_get($v2, "user_id"));
                $checkOrder = CheckOrder::with([])->find(data_get($v2, "check_order_id"));
                TemplateMessageSend::sendEndPayDataToUser($user, $v2, "已逾期");
                TemplateMessageSend::sendEndPayDataToRegionWorker($checkOrder, $v2, "已逾期");
            }
        );
    }
}

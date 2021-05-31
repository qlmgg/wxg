<?php

namespace App\Jobs;

use App\Models\ContractManagement;
use App\Models\TimedTaskLogos;
use App\TemplateMessageSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckContractJob implements ShouldQueue
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
        // 查询即将过期的合同
        $list1 = ContractManagement::with(["checkOrder"])
            ->where("status", "<", 2)
            ->where("is_process", "=", 0)
            ->whereBetween("end_date", [Carbon::today()->firstOfMonth(), Carbon::today()->endOfMonth()])
            ->get();
        $list1->each(
            function ($v1) {
                $v1->status = 2;
                $v1->is_process = 1;
                $v1->save();
                // 合同到期推送模板消息
                TemplateMessageSend::sendEndContractToUser($v1);
            }
        );

        // 查询过期未处理的合同
        $list2 = ContractManagement::with(["checkOrder"])
            ->where("status", "<", 3)
            ->where("is_process", "<", 2)
            ->where("end_date", "<", Carbon::today())
            ->get();
        $list2->each(
            function ($v2) {
                $v2->status = 3;
                $v2->is_process = 2;
                $v2->save();
            }
        );
    }
}

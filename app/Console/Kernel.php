<?php

namespace App\Console;

use App\Jobs\CheckContractJob;
use App\Jobs\CheckPayDataJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // 测试 每分钟执行
        $schedule->job(new CheckContractJob)->everyMinute();
        $schedule->job(new CheckPayDataJob)->everyMinute();

        // // 每个月第一天凌晨 9 时执行
        // $schedule->job(new CheckContractJob)->monthlyOn(1, '9:00');
        // // 每一周第一天凌晨 9 时执行
        // $schedule->job(new CheckPayDataJob)->weeklyOn(1, '9:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

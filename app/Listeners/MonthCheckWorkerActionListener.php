<?php

namespace App\Listeners;

use App\Events\MonthCheckWorkerActionEvent;
use App\Models\BigFile;
use App\Models\MonthCheckWorkerAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class MonthCheckWorkerActionListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MonthCheckWorkerActionEvent  $event
     * @return void
     */
    public function handle(MonthCheckWorkerActionEvent $event)
    {
        return $event->create();
    }

}

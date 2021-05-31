<?php

namespace App\Listeners;

use App\Events\DemandChangeStatusEvent;
use App\Models\Demand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DemandChangeStatusListener
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
     * @param  DemandChangeStatusEvent  $event
     * @return void
     */
    public function handle(DemandChangeStatusEvent $event)
    {
        if($event->demand instanceof Demand){
            $event->demand->changeStatus($event->status);
        }

    }
}

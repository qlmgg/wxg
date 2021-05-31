<?php

namespace App\Listeners;

use App\Events\MenuChangePidsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MenuChangePidsListen
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if ($event instanceof MenuChangePidsEvent){
            $event->menu->changePids();
        }
    }
}

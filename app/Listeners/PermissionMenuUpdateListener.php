<?php

namespace App\Listeners;

use App\Events\MenuUpdateEvent;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PermissionMenuUpdateListener
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
     * @param object $event
     * @return void
     */
    public function handle($event)
    {
        if ($event instanceof MenuUpdateEvent) {
            $menu = $event->getMenu();

            Permission::with([])
                ->where("target_type", "=", get_class($menu))
                ->where("target_id", "=", $menu->id)
                ->update([
                    "name" => $menu->uri
                ]);
        }
    }
}

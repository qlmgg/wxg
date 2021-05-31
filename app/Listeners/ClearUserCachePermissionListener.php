<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ClearUserCachePermissionListener
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
//        Cache::tags([User::USER_ROLE_MENU_PERMISSION_KEY])->flush();

        Cache::tags([Worker::USER_ROLE_MENU_PERMISSION_KEY])->flush();
    }
}

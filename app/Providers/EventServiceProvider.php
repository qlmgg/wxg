<?php

namespace App\Providers;

use App\Events\ClearUserCachePermissionEvent;
use App\Events\DemandChangeStatusEvent;
use App\Events\FreeInspectChangeStatusEvent;
use App\Events\MenuChangePidsEvent;
use App\Events\MenuUpdateEvent;
use App\Events\UserCreateEvent;
use App\Events\UserUpdateEvent;
use App\Events\WorkerRoleSyncEvent;
use App\Listeners\ClearUserCachePermissionListener;
use App\Listeners\DemandChangeStatusListener;
use App\Listeners\FreeInspectChangeStatusListener;
use App\Listeners\MenuChangePidsListen;
use App\Listeners\PermissionMenuUpdateListener;
use App\Listeners\UserRoleSyncListerner;
use App\Listeners\WorkerRoleSyncListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        /* Registered::class => [
             SendEmailVerificationNotification::class,
         ],*/
        MenuUpdateEvent::class => [
            PermissionMenuUpdateListener::class
        ],
        // 菜单排序修改上级
        MenuChangePidsEvent::class => [
            MenuChangePidsListen::class
        ],
        //需求沟通记录增加或修改时需求状态更改事件
        DemandChangeStatusEvent::class => [
            DemandChangeStatusListener::class
        ],

        UserCreateEvent::class => [
            UserRoleSyncListerner::class
        ],

        UserUpdateEvent::class => [
            UserRoleSyncListerner::class
        ],

        ClearUserCachePermissionEvent::class => [
            ClearUserCachePermissionListener::class
        ],

        WorkerRoleSyncEvent::class => [
            WorkerRoleSyncListener::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

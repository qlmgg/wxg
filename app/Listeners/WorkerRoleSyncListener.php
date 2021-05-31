<?php

namespace App\Listeners;

use App\Events\WorkerRoleSyncEvent;
use App\Models\Worker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WorkerRoleSyncListener
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
        if ($event instanceof  WorkerRoleSyncEvent) {
            $worker = $event->getWorker();
            if ($worker) {
                $this->syncRole($worker);
            }
        }
    }


    /**
     * 同步角色
     * @param Worker $worker
     */
    protected function syncRole(Worker $worker) {
        // 根据用户角色ID 分配角色
        $worker->syncRoles($worker->role);
    }
}

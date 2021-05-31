<?php

namespace App\Listeners;

use App\Events\UserCreateEvent;
use App\Events\UserUpdateEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserRoleSyncListerner
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
        if ($event instanceof UserCreateEvent) {
            $user = $event->getUser();
            $this->syncUserRole($user);
        } else if ($event instanceof UserUpdateEvent) {
            $user = $event->getUser();
            $this->syncUserRole($user);
        }
    }


    protected function syncUserRole(User $user)
    {

        if ($user->type == 2) { // 如果是校区账号
            $isFirst = User::with([])
                    ->where("school_id", "=", $user->school_id)
                    ->where("id", "<>", $user->id)
                    ->count() == 0;
            if ($isFirst) {  // 如果是第一个校区账号就是 校区管理员
                $user->role_id = Role::ROLE_CAMPUS_SUPPER;
            } else { // 如果不是第一个 就是校区其他管理员
                $user->role_id = Role::ROLE_CAMPUS_OTHER;
            }
            $user->save();
        }

        // 根据用户角色ID 分配角色
        $user->syncRoles($user->role);
    }

}

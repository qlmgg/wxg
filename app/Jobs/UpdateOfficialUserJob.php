<?php

namespace App\Jobs;

use App\Models\WxOfficialUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateOfficialUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $nextOpenId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($nextOpenId = null)
    {
        $this->nextOpenId = $nextOpenId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $app = get_official_account();

        $list = $app->user->list($this->nextOpenId);

        $data = data_get($list, "data.openid");
        $openidList = collect($data);

        $next_openid = data_get($list, "next_openid");

        $openidList->each(function ($openid) use ($app) {
            $user = $app->user->get($openid);
            $user["app_id"] = data_get($app->getConfig(), "app_id");
            $convertOrigin = WxOfficialUser::convertOrigin($user);

            $officialUser = null;
            if ($convertOrigin) {
                $officialUser = WxOfficialUser::with([])->updateOrCreate([
                    "openid" => data_get($convertOrigin, 'openid')
                ], $convertOrigin);
            }
        });

        if ($openidList->last() != $next_openid) {
            dispatch(new UpdateOfficialUserJob($next_openid));
        }

    }
}

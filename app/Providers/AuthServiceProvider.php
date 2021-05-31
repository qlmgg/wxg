<?php

namespace App\Providers;

use App\AdminUserPasswordGrant;
use App\AdminUserRepository;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
//         'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::tokensCan([
            'wx' => "微信小程序访问",
            'admin' => '后台'
        ]);

        Passport::routes();

    }


    /**
     * 通过手机号和密码
     * @return UserPhoneGrant
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function makeAdminUserPasswordGrant()
    {
        $grant = new AdminUserPasswordGrant(
            $this->app->make(AdminUserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }
}

<?php


namespace App;


use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class AdminUserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {

        if (is_null($model = config('auth.providers.adminUser.model'))) {
            throw new \RuntimeException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($model, 'loadUserByUsername')) {
            $user = (new $model)->loadUserByUsername($username);
        } else {
            throw new \RuntimeException("$model 未提供getUserPhone方法");
        }

        if (!$user) {
            return;
        }

        if (!(Hash::check($password, data_get($user, 'password')))) {
            return;
        }

        return new User($user->getAuthIdentifier());
    }
}

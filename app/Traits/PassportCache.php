<?php


namespace App\Traits;


use App\Exceptions\NoticeException;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;

trait PassportCache
{

    protected $cache_prefix = 'passport_cache_';


    protected function getCachePrefix() {
        return $this->cache_prefix;
    }

    /**
     * 获取缓存前缀
     * @return string
     * @throws NoticeException
     */
    protected function getPassportPrefixKey()
    {
        $token = $this->cachePassportAccessToken();

        $id = data_get($token, 'id');

        if (empty($id)) {
            throw new NoticeException('未获取token的ID');
        }

        return $this->getCachePrefix() . $id;
    }

    /**
     * 获取 passport Token
     * @return Token
     * @throws NoticeException
     */
    protected function cachePassportAccessToken(): Token
    {
        $accessToken = $this->accessToken;

        if ($accessToken instanceof Token) {
            return $accessToken;
        } else if ($accessToken instanceof PersonalAccessTokenResult) {
            return $accessToken->token;
        }else {
            throw new NoticeException('获取Passport token失败');
        }
    }

    /**
     * 设置值
     * @param string $key
     * @param $value
     * @throws NoticeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function passportCacheSet(string $key, $value)
    {
        $prefix = $this->getPassportPrefixKey();

        $origin = Cache::get($prefix);

        if (!$origin) {
            $origin = [];
        }

        $origin[$key] = $value;

        $token = $this->cachePassportAccessToken();

        $expires_at = $token->expires_at;

        if (empty($expires_at)) {
            $expires_at = now()->addMonth();
        }


        return Cache::put($prefix, $origin, $expires_at);
    }

    /**
     * @param $key
     * @return array|mixed
     * @throws NoticeException
     */
    public function passportCacheGet($key)
    {
        $prefix = $this->getPassportPrefixKey();

        $data = Cache::get($prefix);


        return data_get($data, $key);
    }

}

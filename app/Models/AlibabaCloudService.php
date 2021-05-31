<?php


namespace App\Models;


use AlibabaCloud\Client\AlibabaCloud;
use OSS\OssClient;

trait AlibabaCloudService
{
    public static function getClient()
    {

        AlibabaCloud::accessKeyClient(config('alibaba.sts.access_key_id'), config('alibaba.sts.access_ke_secret'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        return AlibabaCloud::rpc()
            ->timeout(40)
            ->connectTimeout(20);
    }


    public static function getOssClient()
    {
        $ossClient = new OssClient(
            config('alibaba.sts.access_key_id'),
            config('alibaba.sts.access_ke_secret'),
            config('alibaba.oss.end_point'));

        return $ossClient;
    }

    /**
     * 获取oss bucket
     * @return string
     */
    public static function getOssBucket()
    {
        return config('alibaba.sts.bucket');
    }
}

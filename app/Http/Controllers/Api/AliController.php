<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlibabaCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AliController extends Controller
{
    use AlibabaCloudService;

    public function ossSts(Request $request)
    {

        $cache_key = "ali_controller_ali_oss_sts";

//        Cache::forget($cache_key);
        $cacheValue = Cache::get($cache_key);
        if (!empty($cacheValue)) {
            $expiration = Carbon::parse($cacheValue['Credentials']['Expiration']);
            $cacheValue['Credentials']['remainder'] = now()->diffInSeconds($expiration);
            return $cacheValue;
        }

        $client = self::getClient();

        $bucket = config('alibaba.sts.bucket');


        $AssumeRolePolicyDocument = [
            "Statement" => [
                [
                    "Action" => "oss:*",
                    "Effect" => "Allow",
                    "Resource" => [
                        "acs:oss:*:*:{$bucket}",
                        "acs:oss:*:*:{$bucket}/*"
                    ]
                ]
            ],
            "Version" => "1"
        ];

        $role_arn = config('alibaba.sts.role_arn');
        $region_id = config('alibaba.sts.region_id');

        $seconds = 3600;

        $result = $client
            ->product('Sts')
            ->scheme('https') // https | http
            ->version('2015-04-01')
            ->action('AssumeRole')
            ->method('POST')
            ->host('sts.aliyuncs.com')
            ->options([
                'query' => [
                    'RegionId' => $region_id,
                    'RoleArn' => $role_arn,
                    'RoleSessionName' => Str::random(),
                    'DurationSeconds' => $seconds,
                    'Policy' => json_encode($AssumeRolePolicyDocument),
                ],
            ])
            ->request();

        $data = $result->toArray();

        $data['Credentials']['region_id'] = $region_id;
        $data['Credentials']['bucket'] = $bucket;
        $data['Credentials']['seconds'] = $seconds;

        $expiration = Carbon::parse($data['Credentials']['Expiration']);
        $expiration->setTimezone(8);
        $expiration = $expiration->subMinutes(5);
        $data['Credentials']['expiration_at'] = $expiration->format("Y-m-d H:i:s");
        $data['Credentials']['created_at'] = $expiration->clone()->subSeconds($seconds)->format("Y-m-d H:i:s");
        $data['Credentials']['remainder'] = now()->diffInSeconds($expiration);
        Cache::put($cache_key, $data, $expiration);

        return $data;
    }
}

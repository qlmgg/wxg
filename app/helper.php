<?php

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * 获取路由信息
 */
if (!function_exists('get_route_information')) {
    function get_route_information(\Illuminate\Routing\Route $route)
    {
        return [
            'domain' => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'prefix' =>  $route->getPrefix(),
            'action' => ltrim($route->getActionName(), '\\'),
        ];
    }
}


if (!function_exists('get_route_information_list')) {
    /**
     * 获取所有路由的基本信息
     * @return \Illuminate\Support\Collection
     * @throws
     */
    function get_route_information_list()
    {
        $router = app()->make(\Illuminate\Routing\Router::class);

        return collect($router->getRoutes())->map(function ($route) {
            return get_route_information($route);
        })->filter(function ($routeInfo) {
            return !empty($routeInfo['uri']);
        })->map(function ($routeInfo) {
            $routeInfo['value'] = $routeInfo['method'] . ':' . $routeInfo['uri'];
            return $routeInfo;
        });
    }
}

if (!function_exists('get_mini_program')) {
    /**
     * 获取小程序插件
     * @return \EasyWeChat\MiniProgram\Application
     */
    function get_mini_program() {
        return \EasyWeChat::miniProgram();
    }
}

if (!function_exists('get_worker_mini_program')) {
    /**
     * 获取员工端小程序
     * @return \EasyWeChat\MiniProgram\Application
     */
    function get_worker_mini_program() {
        return app('wechat.mini_program.worker');
    }

}

if (!function_exists('get_wx_payment')) {
    /**
     * 获取用户端微信小程序支付配置
     * @return \EasyWeChat\Payment\Application
     */
    function get_wx_payment() {
        return app('wechat.payment');
    }
}


if (!function_exists('get_official_account')) {
    /**
     * 获取公众号
     * @return \EasyWeChat\OfficialAccount\Application
     */
    function get_official_account() {
        return app('wechat.official_account');
    }
}



if (!function_exists('log_action')) {
    /**
     * 日志记录
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param $desc
     * @param null $guard
     * @return \App\Models\ActivityLog
     */
    function log_action(Model $model, $desc, $log_name, ?Model $origin = null, $guard = null, ?array $other = null)
    {

        $request = request();

        $data = [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'request_body' => $request->input(),
            'subject_id' => data_get($model, 'id'),
            'subject_type' => get_class($model),
            'description' => $desc,
            'log_name' => $log_name
        ];

        $user = $request->user($guard);

        if ($user) {
            $data['causer_id'] = data_get($user, 'id');
            $data['causer_type'] = get_class($user);
        };

        $changes = $model->getChanges();

        // 去除掉 不记录的日志
        $log_ignore = data_get($model, 'log_ignore');
        if ($log_ignore && is_array($log_ignore)) {
            $changes = collect($changes)->filter(function ($val, $key) use ($log_ignore) {
                return !in_array($key, $log_ignore);
            })->toArray();
        }

        if (!$model->wasRecentlyCreated && empty($origin)) {
            throw new App\Exceptions\NoticeException('修改模型要传入原来的模型');
        }

        $olds = [];
        if (!empty($origin)) {
            foreach ($changes as $key => $val) {
                $olds[$key] = $origin->getAttribute($key);
            }
        }

        $properties = [
            'olds' => $olds,
            'changes' => $changes,
        ];

        if ($other) {
            $properties['other'] = $other;
        }

        $data['properties'] = $properties;


        return \App\Models\ActivityLog::create($data);
    }
}


function log_action_with_properties(Model $model, $desc, array $properties, ?Model $origin = null, $guard = null)
{
    if (count($properties) > 0) {
        return log_action($model, $desc, $origin, $guard, $properties);
    } else {
        return log_action($model, $desc, $origin, $guard);
    }
}

if(!function_exists("is_in_area")) {
    function is_in_area($p_long,$p_lat,$lon,$lat,$radius)
    {
        $PI = 3.14159265;

        $latitude = $p_lat;
        $longitude = $p_long;

        $degree = (24901*1609)/360.0;
        $raidusMile = $radius;

        $dpmLat = 1/$degree;
        $radiusLat = $dpmLat*$raidusMile;
        $minLat = $latitude - $radiusLat;
        $maxLat = $latitude + $radiusLat;

        $mpdLng = $degree*cos($latitude * ($PI/180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng*$raidusMile;
        $minLng = $longitude - $radiusLng;
        $maxLng = $longitude + $radiusLng;

        if($lat>=$minLat && $lat<=$maxLat && $lon>=$minLng &&$lon<=$maxLng){
            return true;
        }else{
            return false;
        }

    }
}

if (!function_exists("sec_to_time")) {
    /**
     *      把秒数转换为时分秒的格式
     *      @param Int $times 时间，单位 秒
     *      @return String
     */
    function sec_to_time($times) {
        $result = '';
        if (0 < $times) {
            $hour = floor($times/3600);
            $minute = floor(($times-3600 * $hour)/60);
            $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);
            if (0 < $hour) {
                $result .= $hour . "时";
            }
            if (0 < $minute) {
                $result .= $minute . "分";
            }
            if (0 < $second) {
                $result .= $second . "秒";
            }
        } else {
            $result .= '- -';
        }
        return $result;
    }
}

if (!function_exists("get_hour")) {
    function get_hour($time) {
        $result = 0;
        if (0 < $time) {
            $result = round($time/3600, 3);
        }
        return $result;
    }
}

if (!function_exists("get_http_host")) {
    function get_http_host () {
        return Request::server("REQUEST_SCHEME") . "://" . Request::server("HTTP_HOST");
    }
}
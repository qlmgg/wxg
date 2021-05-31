<?php


namespace App\Models;


use Illuminate\Http\JsonResponse;

class SimpleResponse extends JsonResponse
{
    public static function success($message, $data = [], $status = 200)
    {
        return static::createFromMessage(1, $message, $data, $status);
    }


    public static function error($message, $data = [], $status = 200)
    {
        return static::createFromMessage(0, $message, $data, $status);
    }

    public static function createFromMessage($code, $message, $data = null, $status)
    {
        $static = new static();
        $static->setData(compact(['code', 'message', 'data']));
        $static->setStatusCode($status);
        return $static;
    }
}

<?php

namespace App\Exceptions;


use App\Models\SimpleResponse;

class NoticeException extends \RuntimeException
{
    public function render() {
        $request = request();

        /*if ($request->ajax()) {
            return [
                "code" => 0,
                "message" => $this->getMessage()
            ];
        } else {
            return [
                "code" => 0,
                "message" => $this->getMessage()
            ];
        }*/


        return SimpleResponse::error($this->getMessage(), [], 400);
    }
}

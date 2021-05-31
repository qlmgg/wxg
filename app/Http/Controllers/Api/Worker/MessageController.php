<?php

namespace App\Http\Controllers\Api\Worker;

use App\Exceptions\NoticeException;
use App\Http\Controllers\Api\SimpleController;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SimpleResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MessageController extends SimpleController
{
    public function getModel()
    {
        // TODO: Implement getModel() method.
        return new Message();
    }

    public function search(Request $request): Builder
    {
        // TODO: Implement search() method.
        $user = $request->user();
        return $this->query($request->input(),$user);
    }

    public function query($query,$user):Builder
    {
        $model = $this->getModel()->with([]);
        $type = data_get($query,"type");
        if($type==1){   //系统消息
            $model = $model->where("type","=",1);
        }
        if($type==2){   //月检消息
            $model = $model->where("type","=",2);
        }
        $model = $model->where("to_worker_id","=",$user->id);
        return $model;
    }

    public function show($id)
    {
        //查看详情时设置为已读
        $m = $this->getModel()->with([])->findOrFail($id);
        if(is_null($m->read_at)){
            $m->read_at = Carbon::now()->format("Y-m-d H:i:s");
            $m->save();
        }
        return $m;
    }

    public function confirm(Request $request,$id)
    {
        $user = $request->user();
        $message = Message::with([])->where("to_worker_id","=",$user->id)->findOrFail($id);
        if($message){
            if(!is_null($message->confirm_at)) throw new NoticeException("消息已确认过");
            $message->confirm_at = Carbon::now()->format("Y-m-d H:i:s");
            $message->save();
            return SimpleResponse::success("成功");
        }
        throw new NoticeException("失败");


    }

}

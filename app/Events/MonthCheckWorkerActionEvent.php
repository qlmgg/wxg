<?php

namespace App\Events;

use App\Models\BigFile;
use App\Models\MonthCheckWorkerAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MonthCheckWorkerActionEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected  $type;
    protected $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    public function create()
    {
        return DB::transaction(function (){
            $model = new MonthCheckWorkerAction();
            $create = $model->with([])->create($this->data);
            $files = data_get($this->data,"files");
            if(!empty($files)){
                //同步上传的文件

                $files = collect($files)->map(function($item)use($create){
                    //获取file的url
                    $item['month_check_worker_action_id'] = $create->id;
                    $file = $this->getFile($item["big_file_id"]);
                    $item['url'] = $file->url;
                    return $item;
                });

                $create->syncFiles($files->toArray());
            }
            return $create;
        });
    }

    /**
     * 根据ID获取文件信息
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }

}

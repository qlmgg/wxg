<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\SimpleController;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use App\Models\BigFile;
use App\Models\PushRecords;
use App\TemplateMessageSend;
use Illuminate\Http\Request;

class PushRecordsController extends SimpleController
{
    //
    protected function getModel()
    {
        return new PushRecords();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel()->with(["region", "worker", "comment.files"]);

        if ($comment_id = data_get($data, "comment_id")) {
            $model->where("comment_id", "=", $comment_id);
        } else {
            return SimpleResponse::error("网络异常");
        }

        return $model;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Models\PushRecords  $pushRecords
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PushRecords  $pushRecords, Request $request)
    {
        $data = $this->validate($request, [
            "comment_id" => ["required", "integer"],
            "title" => ["required", "string", "max:255"],
            "type" => ["required", "in:1,2"],
            "content" => ["required", "string"],
            "worker_id" => ["required", "integer"],
            "region_id" => ["nullable", "integer"]
        ]);

        $data["created_at"] = $data["updated_at"] = date("Y-m-d H:i:s");

        $create = $this->getModel()->with([])->create($data);
        if ($create) {
            //推送成功更改意见状态
            $comment = Comment::with([])->find(data_get($data,"comment_id"));
            $comment->push_status = 1;
            $comment->save();
            // $this->syncPushRecordFiles($data["files_id"], $create);
            TemplateMessageSend::sendFeedbackToUser($create);
            log_action($create, "平台消息一键推送：" . data_get($create, "title"), "平台消息推送管理");
            return SimpleResponse::success("推送成功");
        }

        return SimpleResponse::error("推送失败");
    }

    /**
     *
     * @param  $arr
     * @param  $pushRecords
     */
    protected function syncPushRecordFiles($arr, $pushRecords)
    {
        $big_file_id_arr = array_column($arr, "big_file_id");
        $bigFile = new BigFile();

        $mapByIds = $bigFile->getMapByIds($big_file_id_arr);

        $arr = collect($arr)->map(function ($item) use ($mapByIds) {
            if ($mapByIds->offsetExists($item["big_file_id"])) {
                $mapItem = $mapByIds->get($item["big_file_id"]);
                $item["url"] = data_get($mapItem, "url");
            }
            return $item;
        })->filter(function ($item) {
            return !empty(data_get($item, "url"));
        })->toArray();

        $pushRecords->syncPushRecordFiles($arr);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PushRecords  $pushRecords
     * @return \Illuminate\Http\Response
     */
    public function show(PushRecords $pushRecords)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PushRecords  $pushRecords
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PushRecords $pushRecords)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PushRecords  $pushRecords
     * @return \Illuminate\Http\Response
     */
    public function destroy(PushRecords $pushRecords)
    {
        //
    }
}

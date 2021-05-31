<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BigFile;
use App\Models\Comment;
use App\Models\SimpleResponse;
use App\Rules\MobileRule;
use App\TemplateMessageSend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpinionController extends Controller
{

    public function opinion(Request $request)
    {
        $user = $request->user();
        $data = $this->validate($request,[
            //1平台反馈 2工人反馈 3其它反馈
            "type"=>["required","in:1,2,3"],
            "name"=>["required","string","max:255"],
            "mobile"=>["required",new MobileRule()],
            "content"=>["required","string","max:999"],
            "files"=>["nullable","array"],
            "files.*.id"=>["nullable","integer"],
            "files.*.big_file_id"=>["required_with:files","integer"],
            "files.*.name"=>["required_with:files","string","max:255"]
        ]);

        $data["user_id"] = $user->id;
        if($user->region_id){
            $data["region_id"] = $user->region_id;
        }
        $comment = DB::transaction(function() use($data,$user){
            $comment = Comment::with([])->create($data);
            $files = data_get($data,"files");
            $files = collect($files)->map(function ($item) use($comment){
                $item['comment_id'] = $comment->id;
                $file = $this->getFile($item['big_file_id']);
                $item['url'] = $file->url;
                return $item;
            });
            $comment->syncFiles($files->toArray());
            return $comment;
        });
        TemplateMessageSend::sendOpinionToRegionWorker($user,$comment);
        return SimpleResponse::success("反馈成功");

    }

    /**
     * 根据ID获取文件信息
     * @param Request $request
     * @return array
     */
    public function getFile($id)
    {
        return BigFile::with([])->find($id);
    }

}

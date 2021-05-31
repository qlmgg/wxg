<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\CommentExport;
use App\Http\Controllers\Api\SimpleController;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SimpleResponse;
use App\Models\Comment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;

class CommentsController extends SimpleController
{
    //
    protected function getModel()
    {
        return new Comment();
    }

    protected function search(Request $request): Builder
    {
        return $this->query($request->input());
    }

    public function query(array $data): Builder {

        $model = $this->getModel()->with(['files', 'region']);
        //根据姓名搜索
        if($name = data_get($data,"name")){
            $model->where("name","like","%{$name}%");
        }
        //根据手机号码搜索
        if($mobile = data_get($data,"mobile")){
            $model->where("mobile","like","%{$mobile}%");
        }
        //根据员工状态搜索
        if($status = data_get($data,"status")){
            $model->where("status","=",$status);
        }
        //根据工作状态搜索
        if($work_status = data_get($data,"work_status")){
            $model->where("work_status","=",$work_status);
        }

        return $model;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Comment  $comments
     * @return \Illuminate\Http\Response
     */
    public function show(Comment $comments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Comment  $comments
     * @return \Illuminate\Http\Response
     */
    public function edit(Comment $comments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Comment  $comments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Comment  $comments
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request, Comment $comments, $id)
    {
        $data = $this->validate($request,[
           "status" => ["required","in:0,1"]
        ]);

        $find = $this->getModel()->with([])->find($id);

        if(!$find) return SimpleResponse::error("无效更改");
        $old = clone $find;
        $find->updated_at = date("Y-m-d H:i:s");
        $find->status = data_get($data,"status");
        if ($find->save()) {
            log_action($find, "设置意见反馈设置状态：" . data_get($find, "name"), "意见反馈管理", $old);
            return SimpleResponse::success("成功");
        }
        return SimpleResponse::error("失败");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Comment  $comments
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comments, $id)
    {
        $find = $this->getModel()->with([])->find($id);
        if ($find) {
            $old = clone $find;
        }
        if ($find && $find->delete()) {
            log_action($find, "意见反馈删除：".data_get($find,"name"), "意见反馈管理", $old);
            return SimpleResponse::success("删除成功");
        }
        return SimpleResponse::error("删除失败");
    }

    public function export() 
    {
        return Excel::download(new CommentExport, Carbon::today() . '-comments.xlsx');
    }
}

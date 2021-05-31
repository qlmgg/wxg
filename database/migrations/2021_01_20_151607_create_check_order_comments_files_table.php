<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCheckOrderCommentsFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('check_order_comments_files', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_comments_id")->comment("检查订单评论记录ID");
            $table->integer("big_file_id")->comment("文件信息ID");
            $table->string("name", 52)->nullable()->comment("文件名称");
            $table->string("url")->comment("文件地址");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_check_order_comments_files` comment '检查订单评论上传文件记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('check_order_comments_files');
    }
}

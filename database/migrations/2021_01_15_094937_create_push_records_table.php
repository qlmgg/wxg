<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePushRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('push_records', function (Blueprint $table) {
            $table->id();
            $table->integer("comment_id")->comment("意见反馈ID");
            $table->string("title")->comment("推送标题");
            $table->tinyInteger("type")->default(0)->comment("推送范围 1:全公司 2:区域员工");
            $table->text("content")->comment("推送内容");
            $table->integer("worker_id")->default(0)->comment("操作人");
            $table->integer("region_id")->default(0)->comment("所属区域ID");
            $table->softDeletes();
            $table->timestamps();
        });
        # 表注释
        DB::statement("ALTER TABLE `p_push_records` comment '推送记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('push_records');
    }
}

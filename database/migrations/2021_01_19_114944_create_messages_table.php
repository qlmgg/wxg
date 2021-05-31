<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->bigInteger("to_user_id")->nullable()->index()->comment("用户ID");
            $table->bigInteger("to_worker_id")->nullable()->index()->comment("员工ID");
            $table->nullableMorphs("from"); // 发送人
            $table->string("title")->default("")->comment("消息标题");
            $table->string("content", 5000)->default("")->comment("消息内容");
            $table->smallInteger("type")->default(1)->comment("消息类型 1 系统消息, 2 月检消息");
            $table->smallInteger("can_confirm")->default(0)->comment("0 为不可以确认 1为可以确认");
            $table->timestamp("read_at")->nullable()->comment("是否已读，已读时间");
            $table->timestamp("confirm_at")->nullable()->comment("确认消息有效时间");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}

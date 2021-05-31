<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCommunicationRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('communication_records', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->index()->comment("检查订单ID");
            $table->integer("worker_id")->index()->comment("操作员ID");
            $table->text("content")->nullable()->comment("沟通内容");
            $table->dateTime("estimate_time")->nullable()->comment("预计上门时间");
            $table->tinyInteger("status")->default(0)->comment("状态 1继续沟通 -1作废");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_communication_records` comment '免费检查订单沟通记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('communication_records');
    }
}

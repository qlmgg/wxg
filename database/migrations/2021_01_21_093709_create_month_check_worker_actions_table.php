<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthCheckWorkerActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('month_check_worker_actions', function (Blueprint $table) {
            $table->id();

            $table->integer("check_order_id")->index()->comment("检查合同订单id");
            $table->integer("month_check_worker_id")->index()->comment("月检工人信息id");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("worker_id")->index()->comment("工人id");
            $table->smallInteger("type")->nullable()->comment("操作类型 1后台派单  2工人接单 3工人拒绝 4入场签到 5填写工作内容  6暂停出场签退 7结束签退 8后台异常结束签退");
            $table->dateTime("action_time")->nullable()->comment("操作时间");
            $table->string("address")->nullable()->comment("操作地点");
            $table->decimal("long",32,16)->nullable()->comment("经度");
            $table->decimal("lat",32,16)->nullable()->comment("维度");

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
        Schema::dropIfExists('month_check_worker_actions');
    }
}

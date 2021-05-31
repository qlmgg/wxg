<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthCheckWorkerActionDurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('month_check_worker_action_durations', function (Blueprint $table) {
            $table->id();

            $table->integer("month_check_worker_action_id")->index("worker_action_duration_index")->comment("工人拒绝接单记录ID");
            $table->integer("check_order_id")->index("check_order_duration_index")->comment("检查合同订单id");
            $table->integer("month_check_id")->index("month_check_duration_index")->comment("月检记录id");
            $table->integer("month_check_worker_id")->index("check_worker_duration_index")->comment("月检工人信息id");
            $table->integer("worker_id")->index()->comment("工人id");

            $table->dateTime("start_at")->nullable()->comment("签到时间");
            $table->dateTime("stop_at")->nullable()->comment("签退时间/结束离场时间");
            $table->integer("duration")->nullable()->comment("持续时间(秒)");
            $table->tinyInteger("status")->default(0)->comment("状态：0未签退 1已签退");

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
        Schema::dropIfExists('month_check_worker_action_durations');
    }
}

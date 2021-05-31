<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthCheckWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('month_check_workers', function (Blueprint $table) {
            $table->id();

            $table->integer("check_order_id")->index()->comment("检查合同订单id");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("worker_id")->index()->comment("工人id");
            $table->tinyInteger("status")->default(0)->comment("状态：-1已拒绝 0待接单 1待上门 2检查中 3暂停离场 4已完成");
            $table->bigInteger("service_time")->default(0)->comment("服务时长，按秒记");
            $table->decimal("earnings",10,2)->default(0)->comment("累计受益");
            $table->decimal("client_settlement",10,2)->default(0)->comment("客户结算金额");
            $table->decimal("profit", 10, 2)->default(0)->comment("结算利润");
            $table->tinyInteger("type")->comment("接单类型：1平台派单2工人抢单");
            $table->dateTime("accept_at")->nullable()->comment("接单时间");
            $table->tinyInteger("end_type")->nullable()->comment("结束类型：1正常签退结束2平台异常结束");
            $table->string("reject_reason")->nullable()->comment("拒绝原因");
            $table->dateTime("start_at")->nullable()->comment("第一次签到时间");
            $table->dateTime("stop_at")->nullable()->comment("最终结束离场时间");
            $table->tinyInteger("is_show_client_settlement")->default(0)->comment("是否显示客户结算价格 0默认不显示 1显示");

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
        Schema::dropIfExists('month_check_workers');
    }
}

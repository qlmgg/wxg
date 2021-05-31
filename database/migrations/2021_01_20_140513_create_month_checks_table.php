<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('month_checks', function (Blueprint $table) {
            $table->id();

            $table->integer("check_order_id")->index()->comment("检查合同订单id");
            $table->tinyInteger("type")->default(1)->comment("1免费检查订单 2月检合同订单");
            $table->integer("worker_num")->default(0)->comment("月检人数");
            $table->integer("left_worker_num")->default(0)->comment("剩余月检人数");
            $table->integer("time_length")->nullable()->comment("用工时长(小时)");
            $table->datetime("door_time")->nullable()->comment("预计上门时间");
            $table->decimal("salary_estimate", 10, 2)->default(0)->comment("预计工资");
            $table->text("remark")->nullable()->comment("备注说明");
            $table->tinyInteger("status")->default(0)->comment("状态 0待检查 1检查中 2已完成");

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
        Schema::dropIfExists('month_checks');
    }
}

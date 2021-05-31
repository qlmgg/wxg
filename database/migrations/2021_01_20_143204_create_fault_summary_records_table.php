<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFaultSummaryRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fault_summary_records', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->index()->comment("订单ID");
            $table->integer("month_check_id")->nullable()->index()->comment("月检合同订单 月检记录id");
            $table->integer("month_check_worker_id")->index("check_worker_duration_index")->comment("月检工人信息id");
            $table->integer("worker_id")->index()->comment("员工ID");
            $table->string("title")->nullable()->comment("故障名称");
            $table->tinyInteger("status")->default(0)->comment("状态 0未处理 1已免费处理");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释，注意此处的 `table` 必须是带上前缀的表全名
        DB::statement("ALTER TABLE `p_fault_summary_records` comment '故障汇总记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fault_summary_records');
    }
}

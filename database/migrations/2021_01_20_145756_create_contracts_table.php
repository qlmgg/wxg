<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->unique()->comment("订单ID");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("month_check_worker_id")->index()->comment("月检工人信息id");
            $table->text("remarks")->nullable()->comment("备注");
            $table->integer("worker_id")->index()->comment("员工ID");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_contracts` comment '免费检查订单合同情况'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}

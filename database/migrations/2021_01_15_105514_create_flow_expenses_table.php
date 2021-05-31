<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlowExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flow_expenses', function (Blueprint $table) {
            $table->id();
            $table->integer("region_id")->nullable()->comment("所属区域ID");
            $table->integer("check_order_id")->comment("订单ID");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("month_check_worker_id")->index()->comment("月检工人信息id");
            $table->integer("month_check_worker_action_id")->index()->comment("工人操作记录ID");
            $table->integer("worker_id")->index()->comment("工人ID");
            $table->string("order_code", 52)->comment("订单编号");
            $table->string("name", 15)->comment("员工名称");
            $table->integer("service_time")->default(0)->comment("服务时长");
            $table->decimal("money", 10, 2)->default(0)->comment("支付金额");
            $table->decimal("client_settlement",10,2)->default(0)->comment("客户结算金额");
            $table->decimal("profit", 10, 2)->default(0)->comment("结算利润");
            $table->dateTime("settlement_time")->comment("结算日期");
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
        Schema::dropIfExists('flow_expenses');
    }
}

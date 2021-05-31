<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePaymentManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_management', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id")->index()->comment("用户ID");
            $table->integer("check_order_id")->index()->comment("检查订单ID");
            $table->string("payment_order")->nullable()->comment("支付订单编号");
            $table->decimal("money", 10, 2)->default(0)->comment("应收金额");
            $table->tinyInteger("payment_type")->default(0)->comment("付款方式 1分期付款 2先做后款 3先款后做");
            $table->date("date_payable")->nullable()->comment("应付日期");
            $table->tinyInteger("status")->default(0)->comment("支付状态 0未支付 1已支付,待确认 2已支付");
            $table->dateTime("pay_date")->nullable()->comment("支付日期");
            $table->tinyInteger("pay_type")->default(0)->comment("支付方式 1微信支付 2对公账户");
            $table->integer("worker_id")->index()->comment("操作员ID");
            $table->tinyInteger("is_process")->default(0)->comment("定时处理 0未处理 1即将过期 2已过期");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_payment_management` comment '月检合同订单支付管理'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_management');
    }
}

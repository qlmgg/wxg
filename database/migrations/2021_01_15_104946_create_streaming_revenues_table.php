<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStreamingRevenuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('streaming_revenues', function (Blueprint $table) {
            $table->id();
            $table->integer("region_id")->index()->comment("所属区域ID");
            $table->integer("check_order_id")->index()->comment("订单ID");
            $table->string("order_code", 52)->comment("订单编号");
            $table->string("enterprise_name", 255)->comment("企业名称");
            $table->string("name", 255)->comment("联系人");
            $table->string("mobile", 255)->comment("联系电话");
            $table->decimal("money", 10, 2)->comment("支付金额");
            $table->tinyInteger("pay_type")->default(0)->comment("支付方式 1微信支付 2对公账户");
            $table->dateTime("pay_time")->comment("支付日期");
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
        Schema::dropIfExists('streaming_revenues');
    }
}

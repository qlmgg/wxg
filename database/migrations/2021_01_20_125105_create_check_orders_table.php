<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('check_orders', function (Blueprint $table) {
            $table->id();

            $table->integer("user_id")->nullable()->index()->comment("用户ID");
            $table->integer("free_order_id")->nullable()->index()->comment("免费检查订单ID");
            $table->integer("demand_id")->nullable()->index()->comment("需求ID");
            $table->tinyInteger("type")->default(1)->comment("1免费检查订单 2月检合同订单");
            $table->string("order_code")->unique()->comment("月检单号");
            $table->string("enterprise_name", 52)->comment("企业名称");
            $table->integer("building_area")->default(0)->comment("建筑面积");
            $table->integer("nature_id")->index()->comment("建筑性质ID");
            $table->integer("region_id")->index()->comment("所属区域ID");
            $table->string("name")->nullable()->comment("联系人");
            $table->string("mobile")->nullable()->comment("联系电话");
            $table->string("address")->nullable()->comment("用工地址");
            $table->decimal("long",32,16)->default(0)->comment("经度");
            $table->decimal("lat",32,16)->default(0)->comment("维度");
            $table->string("province")->nullable()->comment("省");
            $table->string("city")->nullable()->comment("市");
            $table->string("area")->nullable()->comment("区");
            $table->tinyInteger("fixed_duty")->default(0)->comment("固定值班 0否1是");
            $table->integer("worker_num")->default(0)->comment("月检人数");
            $table->integer("age_limit")->default(0)->comment("年限");
            $table->decimal("free_amount",10,2)->default(0)->comment("免费额度");
            $table->integer("num_monthly_inspections")->default(0)->comment("月检次数");
            $table->decimal("settling_price", 10, 2)->default(0)->comment("客户结算价");
            $table->decimal("money",10,2)->default(0)->comment("订单金额");
            $table->tinyInteger("payment_type")->default(0)->comment("付款方式 1:分期付款2:先做后款3:先款后做");
            $table->decimal("down_payment",10,2)->default(0)->comment("首付款");
            $table->integer("gift_num")->default(0)->comment("赠送月检次数");
            $table->integer("remaining_service_num")->default(0)->comment("剩余服务次数");
            $table->text("remark")->nullable()->comment("备注说明");
            $table->tinyInteger("status")->default(0)->comment("状态 0待检查 1检查中 2已检查");
            $table->tinyInteger("customer_status")->default(0)->comment("客户状态 0未沟通 1继续沟通 2已完成 -1已作废");
            $table->tinyInteger("is_gift")->default(0)->comment("赠送 0否1是");
            $table->datetime("gift_time")->nullable()->comment("赠送时间");
            $table->datetime("door_time")->nullable()->comment("预计上门时间");
            $table->datetime("free_checkup_time")->nullable()->comment("免费检查时间");
            $table->tinyInteger("pay_status")->default(0)->comment("0未支付 1已支付 2部分支付");
            $table->decimal("remaining_amount", 15, 2)->default(0)->comment("剩余金额");
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
        Schema::dropIfExists('check_orders');
    }
}

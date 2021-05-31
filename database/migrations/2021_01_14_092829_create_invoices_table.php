<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->bigInteger("user_id")->nullable()->index()->comment("用户Id");
            $table->bigInteger("company_id")->nullable()->index()->comment("公司Id");
            $table->smallInteger("type")->default(1)->comment("发票类型 1个人 2企业");
            $table->decimal("money",10,2)->default(0)->comment("开票金额");
            $table->string("name")->nullable()->comment("收件人姓名");
            $table->string("mobile")->nullable()->comment("收件人电话");
            $table->string('address')->nullable()->comment("详细地址");
            $table->string('invoice_number')->nullable()->comment("发票编号");
            $table->smallInteger("status")->default(0)->comment("状态 0未开票 1已开票");

            //顺丰、申通、圆通、中通、韵达 
            $table->smallInteger("courier_company")->default(1)->comment("1顺丰，2申通，3圆通，4中通，5韵达");
            $table->string("courier_number")->nullable()->comment("快递单号");


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
        Schema::dropIfExists('invoices');
    }
}

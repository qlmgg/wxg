<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoyaltiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('royalties', function (Blueprint $table) {
            $table->id();

            $table->smallInteger("level")->default(1)->comment("级别 1小工 2中工 3大工");
            $table->smallInteger("status")->default(0)->comment("状态 0禁用 1启用");
            $table->decimal("customer_money",10,2)->default(0)->comment("客户每小时结算金额");
            $table->decimal("worker_money",10,2)->default(0)->comment("工人每小时结算金额");
            $table->decimal("worker_money_for_customer",10,2)->default(0)->comment("客户显示工人每小时结算金额");

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
        Schema::dropIfExists('royalties');
    }
}

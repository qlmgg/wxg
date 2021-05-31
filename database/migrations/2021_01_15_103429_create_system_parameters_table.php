<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_parameters', function (Blueprint $table) {
            $table->id();
            $table->string("mobile", 25)->comment("客服-手机号码");
            $table->string("landline_number", 25)->comment("客服-座机号码");
            $table->text("activity_description")->nullable()->comment("活动说明");
            $table->string("account", 32)->comment("对公-账号");
            $table->string("open_account_bank", 32)->comment("对公-开户行");
            $table->text("remarks")->nullable()->comment("对公-备注说明");
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
        Schema::dropIfExists('system_parameters');
    }
}

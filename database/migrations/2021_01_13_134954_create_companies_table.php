<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->bigInteger("user_id")->nullable()->index()->comment("用户Id");
            $table->string("name")->nullable()->comment("企业名称");
            $table->string('tax_number')->unique()->nullable()->comment("税号");
            $table->bigInteger("region_id")->nullable()->index()->comment("区域ID");
            $table->string('address')->nullable()->comment("详细地址");

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
        Schema::dropIfExists('companies');
    }
}

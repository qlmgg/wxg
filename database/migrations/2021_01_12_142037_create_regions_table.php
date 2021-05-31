<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();

            $table->string("name")->default("")->comment("区域名称");
            $table->string("province_text")->default("")->comment("省份名称");
            $table->string("province_code")->default("")->comment("省份代码");
            $table->string("city_text")->default("")->comment("城市名称");
            $table->string("city_code")->default("")->comment("城市代码");
            $table->string("district_text")->default("")->comment("区名称");
            $table->string("district_code")->default("")->comment("区代码");
            $table->tinyInteger("is_user")->default(0)->comment("是否绑定区域经理 0否 1是");
            $table->smallInteger("status")->default(0)->comment("状态 0禁用 1启用");
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
        Schema::dropIfExists('regions');
    }
}

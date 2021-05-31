<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demands', function (Blueprint $table) {
            $table->id();

            $table->string("code")->unique()->comment("单号");
            $table->bigInteger("user_id")->nullable()->index()->comment("用户Id");
            $table->string("company_name")->nullable()->comment("企业名称");
            $table->bigInteger("nature_id")->nullable()->index()->comment("建筑性质Id");
            $table->bigInteger("region_id")->nullable()->index()->comment("所在区域Id");

            $table->integer("structure_area")->nullable()->default(0)->comment("建筑面积");
            $table->string("name")->nullable()->comment("联系人姓名");
            $table->string("mobile")->nullable()->comment("联系人手机");
            $table->string('address')->nullable()->comment("详细地址");

            $table->decimal("longitude",32,16)->nullable()->comment("经度");
            $table->decimal("latitude",32,16)->nullable()->comment("纬度");

            $table->string("province_text")->nullable()->comment("省份名称");
            $table->string("province_code")->nullable()->comment("省份代码");
            $table->string("city_text")->nullable()->comment("城市名称");
            $table->string("city_code")->nullable()->comment("城市代码");
            $table->string("district_text")->nullable()->comment("区名称");
            $table->string("district_code")->nullable()->comment("区代码");

            $table->datetime("door_at")->nullable()->comment("预计时间");
            $table->smallInteger("status")->default(0)->comment("状态 0待沟通 1待上门检查 2成功 -1已作废");

            $table->text("user_demand")->nullable()->comment("用户需求");

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
        Schema::dropIfExists('demands');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateContractManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_management', function (Blueprint $table) {
            $table->id();
            $table->integer("region_id")->index()->comment("所属地区ID");
            $table->integer("check_order_id")->index()->comment("检查订单ID");
            $table->string("enterprise_name")->nullable()->comment("企业名称");
            $table->string("name")->nullable()->comment("联系人");
            $table->string("mobile")->nullable()->comment("联系方式");
            $table->decimal("money", 10, 2)->default(0)->comment("合同金额");
            $table->dateTime("signature_date")->nullable()->comment("签署日期");
            $table->integer("age_limit")->default(0)->comment("年限 (单位:月)");
            $table->dateTime("end_date")->nullable()->comment("到期日期");
            $table->tinyInteger("status")->default(0)->comment("状态 1进行中 2即将过期 3已过期");
            $table->string("contracts_file")->comment("合同文件");
            $table->text("remarks")->nullable()->comment("备注");
            $table->tinyInteger("is_process")->default(0)->comment("定时处理 0未处理 1即将过期 2已过期");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_contract_management` comment '合同管理'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_management');
    }
}

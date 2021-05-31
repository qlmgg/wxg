<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMaterialListRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material_list_records', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->index()->comment("检查订单ID");
            $table->integer("month_check_id")->nullable()->index()->comment("月检合同订单 月检记录id");
            $table->integer("month_check_worker_id")->nullable()->index()->comment("月检工人信息id");
            $table->integer("worker_id")->nullable()->index()->comment("员工ID");
            $table->integer("goods_id")->index()->comment("材料ID");
            $table->string("name")->comment("材料名称");
            $table->string("sku")->nullable()->comment("材料型号");
            $table->integer("good_sku_id")->nullable()->index()->comment("商品规格id");
            $table->decimal("price", 10, 2)->default(0)->comment("单价");
            $table->integer("num")->default(0)->comment("数量");
            $table->decimal("total_price", 10, 2)->default(0)->comment("总额小计");
            $table->tinyInteger("type")->default(0)->comment("材料类型 1赠送 2购买");
            $table->integer("gift_num")->default(0)->comment("赠送月检次数");
            $table->dateTime("gift_time")->nullable()->comment("赠送时间");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_material_list_records` comment '免费检查订单/月检订单材料清单记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('material_list_records');
    }
}

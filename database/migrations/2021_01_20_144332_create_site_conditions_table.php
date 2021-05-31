<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSiteConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_conditions', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->index()->comment("检查订单ID");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("month_check_worker_id")->index("check_worker_duration_index")->comment("月检工人信息id");
            $table->integer("worker_id")->index()->comment("员工ID");
            $table->text("remarks")->nullable()->comment("备注");
            $table->tinyInteger("type")->default(1)->comment("1免费-现场情况 2月检-月检表");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释，注意此处的 `table` 必须是带上前缀的表全名
        DB::statement("ALTER TABLE `p_site_conditions` comment '现场情况/月检表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_conditions');
    }
}

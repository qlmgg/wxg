<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCheckOrderCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('check_order_comments', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id")->nullable()->index()->comment("用户ID");
            $table->integer("check_order_id")->index()->comment("检查订单ID");
            $table->integer("month_check_id")->nullable()->index()->comment("月检合同订单 月检记录id");
            $table->text("content")->nullable()->comment("评价内容");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_check_order_comments` comment '检查订单评论记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('check_order_comments');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateJobContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_contents', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->index()->comment("检查合同订单id");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("month_check_worker_action_id")->index("worker_action_job_index")->comment("工人操作记录ID");
            $table->integer("month_check_worker_id")->index("check_worker_job_index")->comment("月检工人信息id");
            $table->integer("worker_id")->index()->comment("工人id");
            $table->string("title")->comment("名称");
            $table->tinyInteger("type")->default(1)->comment("1固定检查项 2新增检查项");
            $table->text("remarks")->nullable()->comment("备注内容");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_job_contents` comment '月检工作内容'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_contents');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFaultSummaryRecordFliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fault_summary_record_flies', function (Blueprint $table) {
            $table->id();
            $table->integer("fault_summary_record_id")->index()->comment("故障汇总记录ID");
            $table->integer("big_file_id")->index()->comment("文件信息ID");
            $table->string("name")->nullable()->comment("文件名称");
            $table->string("url")->comment("文件地址");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释，注意此处的 `table` 必须是带上前缀的表全名
        DB::statement("ALTER TABLE `p_fault_summary_record_flies` comment '故障汇总上传文件记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fault_summary_record_flies');
    }
}

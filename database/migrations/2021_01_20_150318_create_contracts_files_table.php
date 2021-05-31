<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateContractsFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts_files', function (Blueprint $table) {
            $table->id();
            $table->integer("contracts_id")->comment("免费检查订单合同情况ID");
            $table->integer("big_file_id")->comment("文件信息ID");
            $table->string("name", 52)->nullable()->comment("文件名称");
            $table->string("url")->comment("文件地址");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释
        DB::statement("ALTER TABLE `p_contracts_files` comment '免费检查订单合同情况上传文件记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts_files');
    }
}

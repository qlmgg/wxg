<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSiteConditionsFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_conditions_files', function (Blueprint $table) {
            $table->id();
            $table->integer("site_conditions_id")->index()->comment("现场情况/月检表ID");
            $table->integer("big_file_id")->index()->comment("文件信息ID");
            $table->string("name", 52)->nullable()->comment("文件名称");
            $table->string("url")->comment("文件地址");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释，注意此处的 `table` 必须是带上前缀的表全名
        DB::statement("ALTER TABLE `p_site_conditions_files` comment '现场情况/月检表上传文件记录'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_conditions_files');
    }
}

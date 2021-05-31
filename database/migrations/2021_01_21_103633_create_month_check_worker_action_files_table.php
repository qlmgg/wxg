<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthCheckWorkerActionFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('month_check_worker_action_files', function (Blueprint $table) {
            $table->id();

            $table->integer("month_check_worker_action_id")->index("worker_action_id_files_index")->comment("工人拒绝接单记录ID");
            $table->integer("big_file_id")->index()->comment("文件信息ID");
            $table->string("name")->nullable()->comment("文件名称");
            $table->string("url")->nullable()->comment("文件地址");
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
        Schema::dropIfExists('month_check_worker_action_files');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBigFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('big_files', function (Blueprint $table) {
            $table->id();
            $table->string('sha1')->nullable()->index()->comment('文件识别码');
            $table->string('size')->default(0)->comment('文件大小');
            $table->string('path')->nullable()->comment('文件位置');
            $table->string('extension')->default('')->comment('后缀');
            $table->string('content_type')->nullable()->comment('文件类型');
            $table->string('client_original_name')->comment('客户端文件名');
            $table->boolean('is_exist')->default(false)->comment('在oss是否存在');
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
        Schema::dropIfExists('big_files');
    }
}

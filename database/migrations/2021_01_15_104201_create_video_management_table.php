<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_management', function (Blueprint $table) {
            $table->id();
            $table->string("title")->comment("视频标题");
            $table->string("video_url")->comment("视频链接");
            $table->tinyInteger("status")->default(0)->comment("状态 0禁用 1启用");
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
        Schema::dropIfExists('video_management');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_contents', function (Blueprint $table) {
            $table->id();
            $table->string("title")->comment("标题");
            $table->longText("content")->comment("内容");
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
        Schema::dropIfExists('website_contents');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string("name")->default('')->index()->comment("菜单名称");
            $table->string("uri")->default("")->comment("菜单路径");
            $table->string("icon_class")->nullable()->comment("图标样式");
            $table->string("type")->comment("类型 1:侧边 2按钮, 3占位");
            $table->bigInteger("p_id")->default(0)->index()->comment("上级ID");
            $table->string('pids', 500)->nullable()->comment('上级编号数');
            $table->string("method")->nullable()->comment("请求方式");
            $table->integer("sort")->default(0)->comment("排序");
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
        Schema::dropIfExists('menus');
    }
}

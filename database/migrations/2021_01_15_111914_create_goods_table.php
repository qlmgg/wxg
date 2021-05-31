<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->id();
            $table->integer("brand_id")->comment("所属品牌");
            $table->string("title", 25)->comment("标题");
            $table->decimal("price", 10, 2)->default(0)->comment("价格");
            $table->string("unit")->nullable()->comment("单位");
            $table->text("remark")->nullable()->comment("备注说明");
            $table->tinyInteger("status")->default(0)->comment("状态 0禁用1启用");
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
        Schema::dropIfExists('goods');
    }
}

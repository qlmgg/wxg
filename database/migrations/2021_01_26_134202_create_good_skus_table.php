<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_skus', function (Blueprint $table) {
            $table->id();
            $table->integer("goods_id")->index()->comment("材料ID");
            $table->string("name")->comment("规格名称");
            $table->decimal("price", 10, 2)->comment("规格价格");
            $table->softDeletes();
            $table->timestamps();
        });
        # 添加表注释，注意此处的 `table` 必须是带上前缀的表全名
        DB::statement("ALTER TABLE `p_good_skus` comment '商品规格'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('good_sku');
    }
}

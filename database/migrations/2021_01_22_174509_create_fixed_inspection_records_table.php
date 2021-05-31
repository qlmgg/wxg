<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedInspectionRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixed_inspection_records', function (Blueprint $table) {
            $table->id();
            $table->integer("check_order_id")->index()->comment("订单ID");
            $table->integer("fixed_check_items_id")->index()->comment("固定项ID");
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
        Schema::dropIfExists('fixed_inspection_records');
    }
}

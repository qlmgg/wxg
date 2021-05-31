<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRejectOrderRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reject_order_records', function (Blueprint $table) {
            $table->id();

            $table->integer("check_order_id")->index()->comment("检查合同订单id");
            $table->integer("month_check_id")->index()->comment("月检记录id");
            $table->integer("month_check_worker_id")->index()->comment("派单ID");
            $table->integer("worker_id")->index()->comment("工人id");
            $table->string("reject_reason")->nullable()->comment("拒绝原因");

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
        Schema::dropIfExists('reject_order_records');
    }
}

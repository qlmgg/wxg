<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemandCommunicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demand_communications', function (Blueprint $table) {
            $table->id();

            $table->bigInteger("admin_user_id")->nullable()->index()->comment("沟通人Id");
            $table->bigInteger("demand_id")->nullable()->index()->comment("需求单Id");
            $table->string("content")->nullable()->comment("沟通内容");
            $table->datetime("door_at")->nullable()->comment("预计上门时间");
            $table->smallInteger("status")->default(1)->comment("1继续沟通 -1作废");
            $table->nullableMorphs("communicator");

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
        Schema::dropIfExists('demand_communications');
    }
}

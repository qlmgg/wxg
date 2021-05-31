<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable()->comment('短信类型');
            $table->integer('expires_in')->nullable()->comment('过期时间:秒');
            $table->string('phone')->comment('电话号码');
            $table->string('drive', 100)->default('dayu')->comment('驱动');
            $table->string('template_code')->comment('短信模板');
            $table->json('param')->comment('发送的内容');
            $table->json('result')->nullable()->comment('通知结果');
            $table->tinyInteger('status')->default(0)->comment('使用状态 0未使用;1已使用');
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
        Schema::dropIfExists('sms');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWxUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wx_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->nullable()->unique()->comment("用户ID");
            $table->string("app_id")->nullable()->comment("微信AppID");
            $table->string("nickname")->nullable()->comment("用户昵称");
            $table->string("avatar_url")->nullable()->comment("用户头像");
            $table->string("gender")->nullable()->comment("性别");
            $table->string("country")->nullable()->comment("国家");
            $table->string("province")->nullable()->comment("省份");
            $table->string("city")->nullable()->comment("城市");
            $table->string("language")->nullable()->comment("语言");
            $table->string("openid")->nullable()->unique()->comment("openid");
            $table->string("unionid")->nullable()->unique()->comment("unionid");
            $table->string("mobile")->unique()->nullable()->comment("手机号");
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
        Schema::dropIfExists('wx_users');
    }
}

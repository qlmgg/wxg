<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWxOfficialUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wx_official_users', function (Blueprint $table) {
            $table->id();
            $table->smallInteger("subscribe")->nullable()->comment("是否关注 1关注 0未关注");
            $table->string("openid")->nullable()->unique()->comment("openid");
            $table->string("app_id")->nullable()->comment("app_id");
            $table->string("nickname")->nullable()->comment("昵称");
            $table->string("sex")->nullable()->comment("性别");
            $table->string("country")->nullable()->comment("国家");
            $table->string("province")->nullable()->comment("省");
            $table->string("city")->nullable()->comment("城市");
            $table->string("language")->nullable()->comment("用户的语言");
            $table->string("headimgurl")->nullable()->comment("用户头像");
            $table->dateTime("subscribe_at")->nullable()->comment("关注时间");
            $table->string("unionid")->nullable()->unique()->comment("开放平台唯一号");
            $table->string("remark")->nullable()->comment("备注");
            $table->string("groupid")->nullable()->comment("分组ID");
            $table->json("tagid_list")->nullable()->comment("标签ID列表");
            $table->string("subscribe_scene")->nullable()->comment("关注的渠道");
            $table->string("qr_scene")->nullable()->comment("扫码场景");
            $table->string("qr_scene_str")->nullable()->comment("扫码场景描述");
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
        Schema::dropIfExists('wx_official_users');
    }
}

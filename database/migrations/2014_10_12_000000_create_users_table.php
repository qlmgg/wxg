<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->nullable()->comment("账号");
            $table->string("avatar_url")->nullable()->comment("用户头像");
            $table->string("name")->nullable()->comment("姓名");
            $table->string("link_name")->nullable()->comment("联系人");
            $table->string("mobile")->unique()->nullable()->comment("电话");
            $table->string('email')->unique()->nullable()->comment("邮箱");
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->bigInteger("role_id")->nullable()->index()->comment("角色Id/组别");
            $table->string("role_name")->nullable()->comment("身份");
            $table->smallInteger("status")->default(1)->comment("状态 1启用 0禁用");
            $table->nullableMorphs("model");

            $table->bigInteger("region_id")->nullable()->index()->comment("区域ID");
            $table->string('address')->nullable()->comment("详细地址");
            $table->smallInteger("type")->default(0)->comment("用户类型 0未认证 1个人 2企业");
            $table->string('id_card')->unique()->nullable()->comment("身份证号");


            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}

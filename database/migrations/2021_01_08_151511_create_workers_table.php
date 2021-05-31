<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string("name")->default("")->comment("用户名");
            $table->string("mobile")->nullable()->unique()->comment("手机号");
            $table->string("password")->nullable()->comment("密码");
            $table->string("openid")->unique()->nullable()->comment("微信openid");

            $table->bigInteger("role_id")->nullable()->index()->comment("角色ID 1后台超级管理员 2区域经理 ...其他");
            $table->smallInteger("type")->nullable()->comment("人员类型 1后台管理员 2区域经理 3工人...其他");
            $table->tinyInteger("is_worker")->default(0)->comment("区域经理是否为员工 0否 1是");
            $table->datetime("entry_at")->nullable()->comment("入职时间");
            $table->bigInteger("region_id")->nullable()->index()->comment("区域ID");

            $table->smallInteger("level")->default(1)->comment("级别 1小工 2中工 3大工");
            $table->smallInteger("status")->default(0)->comment("状态 0禁用 1启用");
            $table->smallInteger("work_status")->default(0)->comment("工作状态 0休息中 1空闲中 2工作中");
            $table->smallInteger("pre_work_status")->nullable()->comment("工作状态 0休息中 1空闲中 2工作中");
            $table->string("rest_reason")->nullable()->comment("最近一次休息原因");

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
        Schema::dropIfExists('workers');
    }
}

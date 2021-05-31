<?php
/*
 * @Author: your name
 * @Date: 2021-01-14 19:28:07
 * @LastEditTime: 2021-01-15 09:25:42
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \protectionApi\database\migrations\2021_01_14_192807_create_comments_table.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id")->nullable()->index()->comment("用户ID");
            $table->tinyInteger("type")->default(0)->comment("1平台反馈 2工人反馈 3其它反馈");
            $table->string("name", 25)->comment("反馈人姓名");
            $table->string("mobile", 25)->comment("反馈人电话");
            $table->text("content")->comment("反馈说明");
            $table->tinyInteger("status")->default(0)->comment("状态：0未处理 1已处理");
            $table->tinyInteger("push_status")->default(0)->comment("推送状态：0未推送 1已推送");
            $table->integer("region_id")->default(0)->comment("所属区域ID");
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
        Schema::dropIfExists('comments');
    }
}

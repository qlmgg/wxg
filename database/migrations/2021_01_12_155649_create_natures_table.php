<?php
/*
 * @Author: your name
 * @Date: 2021-01-12 15:56:49
 * @LastEditTime: 2021-01-13 17:09:14
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \protectionApi\database\migrations\2021_01_12_155649_create_natures_table.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('natures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->tinyInteger('status')->default(0)->comment('状态：0禁用 1启用');
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
        Schema::dropIfExists('natures');
    }
}

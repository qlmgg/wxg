<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MenusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('menus')->delete();
        
        \DB::table('menus')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => '免费合同',
                'uri' => '/regional/index',
                'icon_class' => 'fa fa-edit',
                'type' => '1',
                'p_id' => 0,
                'pids' => '0,1,',
                'method' => 'GET',
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 10:24:29',
                'updated_at' => '2021-01-22 16:41:28',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => '大客户管理1',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 4,
                'pids' => '0,1,2,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 15:36:40',
                'updated_at' => '2021-01-22 16:35:50',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => '222',
                'uri' => '/regional/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 0,
                'pids' => '0,3,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 15:39:23',
                'updated_at' => '2021-01-22 16:43:18',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => '人员管理',
                'uri' => '/regional/index',
                'icon_class' => NULL,
                'type' => '2',
                'p_id' => 2,
                'pids' => '0,1,2,4,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 15:40:51',
                'updated_at' => '2021-01-22 15:40:51',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => '国企客户',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 6,
                'pids' => '0,2,5,6,5,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 15:41:02',
                'updated_at' => '2021-01-22 16:46:56',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => '大客户管理',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 5,
                'pids' => '0,2,5,6,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 16:26:54',
                'updated_at' => '2021-01-22 16:26:54',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => '企业合同',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 1,
                'pids' => '0,1,7,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 16:55:24',
                'updated_at' => '2021-01-22 16:55:24',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => '私人合同',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 1,
                'pids' => '0,1,8,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 16:58:12',
                'updated_at' => '2021-01-22 16:58:12',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => '国企客户1',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 7,
                'pids' => '0,1,7,9,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => '2021-01-22 17:03:29',
                'created_at' => '2021-01-22 16:58:28',
                'updated_at' => '2021-01-22 17:03:29',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => '私企客户',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 7,
                'pids' => '0,1,7,10,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 16:58:43',
                'updated_at' => '2021-01-22 16:58:43',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => '菜单管理',
                'uri' => '/menu/index',
                'icon_class' => NULL,
                'type' => '1',
                'p_id' => 0,
                'pids' => '0,11,',
                'method' => NULL,
                'sort' => 0,
                'deleted_at' => NULL,
                'created_at' => '2021-01-22 16:59:54',
                'updated_at' => '2021-01-22 16:59:54',
            ),
        ));
        
        
    }
}
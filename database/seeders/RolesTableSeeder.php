<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('roles')->delete();
        
        \DB::table('roles')->insert(array (
            0 => 
            array (
                'id' => 1,
                'status' => 1,
                'name' => '管理员',
                'guard_name' => 'admin',
                'deleted_at' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'status' => 1,
                'name' => '区域经理',
                'guard_name' => 'admin',
                'deleted_at' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'status' => 0,
                'name' => '子频道管理',
                'guard_name' => 'admin',
                'deleted_at' => '2021-01-24 09:42:37',
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-24 09:42:37',
            ),
            3 => 
            array (
                'id' => 4,
                'status' => 0,
                'name' => '全频道管理1',
                'guard_name' => 'admin',
                'deleted_at' => '2021-01-29 15:23:28',
                'created_at' => '2021-01-24 09:30:10',
                'updated_at' => '2021-01-29 15:23:28',
            ),
            4 => 
            array (
                'id' => 5,
                'status' => 1,
                'name' => '经理',
                'guard_name' => 'admin',
                'deleted_at' => NULL,
                'created_at' => '2021-01-28 16:07:01',
                'updated_at' => '2021-02-01 10:45:51',
            ),
            5 => 
            array (
                'id' => 6,
                'status' => 1,
                'name' => '全区管理员',
                'guard_name' => 'admin',
                'deleted_at' => NULL,
                'created_at' => '2021-01-29 15:23:36',
                'updated_at' => '2021-01-29 15:23:36',
            ),
        ));
        
        
    }
}
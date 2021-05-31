<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('permissions')->delete();
        
        \DB::table('permissions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => '/regional/index',
                'guard_name' => 'admin',
                'target_type' => 'App\\Models\\Menu',
                'target_id' => 1,
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-22 17:59:28',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => '/regional/index',
                'guard_name' => 'admin',
                'target_type' => 'App\\Models\\Menu',
                'target_id' => 3,
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-22 17:59:28',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => '/menu/index',
                'guard_name' => 'admin',
                'target_type' => 'App\\Models\\Menu',
                'target_id' => 7,
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-22 17:59:28',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => '/menu/index',
                'guard_name' => 'admin',
                'target_type' => 'App\\Models\\Menu',
                'target_id' => 8,
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-22 17:59:28',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => '/menu/index',
                'guard_name' => 'admin',
                'target_type' => 'App\\Models\\Menu',
                'target_id' => 10,
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-22 17:59:28',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => '/menu/index',
                'guard_name' => 'admin',
                'target_type' => 'App\\Models\\Menu',
                'target_id' => 11,
                'created_at' => '2021-01-22 17:59:28',
                'updated_at' => '2021-01-22 17:59:28',
            ),
        ));
        
        
    }
}
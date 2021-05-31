<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleHasPermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('role_has_permissions')->delete();
        
        \DB::table('role_has_permissions')->insert(array (
            0 => 
            array (
                'permission_id' => 1,
                'role_id' => 3,
            ),
            1 => 
            array (
                'permission_id' => 2,
                'role_id' => 3,
            ),
            2 => 
            array (
                'permission_id' => 3,
                'role_id' => 3,
            ),
            3 => 
            array (
                'permission_id' => 4,
                'role_id' => 3,
            ),
            4 => 
            array (
                'permission_id' => 5,
                'role_id' => 3,
            ),
            5 => 
            array (
                'permission_id' => 6,
                'role_id' => 3,
            ),
            6 => 
            array (
                'permission_id' => 1,
                'role_id' => 4,
            ),
            7 => 
            array (
                'permission_id' => 2,
                'role_id' => 4,
            ),
            8 => 
            array (
                'permission_id' => 3,
                'role_id' => 4,
            ),
            9 => 
            array (
                'permission_id' => 4,
                'role_id' => 4,
            ),
            10 => 
            array (
                'permission_id' => 5,
                'role_id' => 4,
            ),
            11 => 
            array (
                'permission_id' => 6,
                'role_id' => 4,
            ),
            12 => 
            array (
                'permission_id' => 1,
                'role_id' => 5,
            ),
            13 => 
            array (
                'permission_id' => 2,
                'role_id' => 5,
            ),
            14 => 
            array (
                'permission_id' => 3,
                'role_id' => 5,
            ),
            15 => 
            array (
                'permission_id' => 4,
                'role_id' => 5,
            ),
            16 => 
            array (
                'permission_id' => 5,
                'role_id' => 5,
            ),
            17 => 
            array (
                'permission_id' => 6,
                'role_id' => 5,
            ),
            18 => 
            array (
                'permission_id' => 1,
                'role_id' => 6,
            ),
            19 => 
            array (
                'permission_id' => 2,
                'role_id' => 6,
            ),
            20 => 
            array (
                'permission_id' => 3,
                'role_id' => 6,
            ),
            21 => 
            array (
                'permission_id' => 4,
                'role_id' => 6,
            ),
            22 => 
            array (
                'permission_id' => 5,
                'role_id' => 6,
            ),
            23 => 
            array (
                'permission_id' => 6,
                'role_id' => 6,
            ),
        ));
        
        
    }
}
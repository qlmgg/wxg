<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('workers')->delete();

        \DB::table('workers')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => 'admin',
                'mobile' => '18381766199',
                'password' => '$2y$10$DfywmO.k0dqOlS4VDTKOJuffbiYQWFHWF.uCZIJ0LfDfTx8x1RRGa',
                'openid' => NULL,
                'entry_at' => '2020-01-16 00:00:00',
                'region_id' => 20,
                'level' => 1,
                'status' => 1,
                'work_status' => 1,
                'pre_work_status' => 0,
                'rest_reason' => '没有原因',
                'role_id' => 1,
                'type' => 1,
                'deleted_at' => NULL,
                'created_at' => '2021-01-16 11:27:11',
                'updated_at' => '2021-01-27 11:04:28',
            ),
        ));


    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OauthClientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('oauth_clients')->delete();
        
        \DB::table('oauth_clients')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => NULL,
                'name' => 'Door Personal Access Client',
                'secret' => 'PQGaBiYAKUfK8qZWayRVAEuh0O5KDnYeNXYGBwsS',
                'provider' => NULL,
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => '2021-01-06 10:14:49',
                'updated_at' => '2021-01-06 10:14:49',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => NULL,
                'name' => 'Door Password Grant Client',
                'secret' => '6NE1D6MegWzERvobhZ2pehQ7tahCzkXD99ogmJGj',
                'provider' => 'users',
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2021-01-06 10:14:50',
                'updated_at' => '2021-01-06 10:14:50',
            ),
            2 => 
            array (
                'id' => 3,
                'user_id' => NULL,
                'name' => 'admin',
                'secret' => 'TQCcQ676QOIhttedK1YjCXz6Y3cbeG4bKRJRsLhN',
                'provider' => 'adminUser',
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2021-01-11 17:50:08',
                'updated_at' => '2021-01-11 17:50:08',
            ),
            3 => 
            array (
                'id' => 4,
                'user_id' => NULL,
                'name' => 'worker',
                'secret' => 'WlQFUvGwpvAvUv5Yt3aMSBRq9qsP1Czgcj75Oq4X',
                'provider' => 'worker',
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2021-01-16 09:21:33',
                'updated_at' => '2021-01-16 09:21:33',
            ),
        ));
        
        
    }
}
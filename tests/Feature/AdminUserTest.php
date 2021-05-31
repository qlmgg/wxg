<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }


    public function testCreate() {

        $user = AdminUser::with([])->updateOrCreate([
            "username" => "admin",
        ], [
            "mobile" => "17381832386",
            "password" => Hash::make("123456"),
            "role_id" => 1,
            "header_img" => "",
            "status" => 1,
            "name" => "后台管理员"
        ]);


        dd($user);
    }
}

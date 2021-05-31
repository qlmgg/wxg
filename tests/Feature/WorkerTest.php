<?php

namespace Tests\Feature;

use App\Events\WorkerRoleSyncEvent;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkerTest extends TestCase
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


    public function testSyncRole() {
        /**
         * @var Worker $worker
         */
        $worker = Worker::with([])->findOrFail(5);

        event(new WorkerRoleSyncEvent($worker));

        dd($worker->roles()->get());
    }
}

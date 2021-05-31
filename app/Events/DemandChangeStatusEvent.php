<?php

namespace App\Events;

use App\Models\Demand;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DemandChangeStatusEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $demand;
    public $status;

    /**
     * DemandChangeStatusEvent constructor.
     * @param $demand
     * @param $status
     */
    public function __construct($demand,$status)
    {
        $this->demand = $demand;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

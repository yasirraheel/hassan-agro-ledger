<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MultiPaymentProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payments;
    public $business_id;
    public $reference_id;
    public $platform;
    public $receiveAmount;
    public $party_id;
    public $date;
    public $note;
    public $changeAmount;

    /**
     * Create a new event instance.
     */
    public function __construct($payments, $reference_id, $platform, $receiveAmount = 0, $party_id = null, $date = null, $note = null, $changeAmount = 0)
    {
        $this->payments = $payments;
        $this->reference_id = $reference_id;
        $this->platform = $platform;
        $this->receiveAmount = $receiveAmount;
        $this->party_id = $party_id;
        $this->date = $date;
        $this->note = $note;
        $this->changeAmount = $changeAmount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}

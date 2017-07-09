<?php

namespace ZapsterStudios\TeamPay\Events\Subscriptions;

use App\Team;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelled
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Team $team)
    {
        $this->team = $team;
    }
}

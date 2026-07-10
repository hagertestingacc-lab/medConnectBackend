<?php

namespace App\Events;

use App\Models\Chat\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public Message $message;
    public function __construct(Message $message)
    {
        $this->message = $message->load("sender");
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id)
        ];
    }
    public function broadcastAs(): string
    {
        return 'NewMessageSent';
    }

    // Data that Flutter will receive
    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'body'            => $this->message->body,
            'conversation_id' => $this->message->conversation_id,
            'sender'          => [
                'id'   => $this->message->sender->id,
                'name' => $this->message->sender->fullname,
                'role' => $this->message->sender->role,
            ],
            'sent_at' => $this->message->created_at->toISOString(),
            'read_at' => null,
        ];
    }
}

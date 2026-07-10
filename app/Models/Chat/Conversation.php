<?php

namespace App\Models\Chat;

use App\Models\AllUserPart\AllUser;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['participant_one', 'participant_two', 'last_message_at'];

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function userOne()
    {
        return $this->belongsTo(AllUser::class, 'participant_one');
    }

    public function userTwo()
    {
        return $this->belongsTo(AllUser::class, 'participant_two');
    }

    public function otherUser($currentUserId)
    {
        return  $this->participant_one == $currentUserId
            ? $this->userTwo
            : $this->userOne;
    }

    // Find existing conversation or create one (always sorts IDs to avoid duplicates)
    public static function findOrCreate($userOneId, $userTwoId)
    {
        $ids = [$userOneId, $userTwoId];
        sort($ids);

        return self::firstOrCreate([
            'participant_one' => $ids[0],
            'participant_two' => $ids[1],
        ]);
    }
}

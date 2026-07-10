<?php

namespace App\Models\Chat;

use App\Models\AllUserPart\AllUser;
use App\Models\ProductPart\Product;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'read_at', 'product_id'];

    public function sender()
    {
        return $this->belongsTo(AllUser::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

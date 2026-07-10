<?php

namespace App\Http\Controllers\api;

use App\Events\NewMessageSent;
use App\Http\Controllers\Controller;
use App\Models\AllUserPart\AllUser;
use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\SupplierPart\Supplier;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // Get users the current user is allowed to chat with
    public function contacts(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->isDoctor()) {
                $contacts = AllUser::where('role', 'supplier')->get(['id', 'fullname', 'role', "email"]);
            } elseif ($user->isSupplier()) {
                $contacts = AllUser::where(function ($q) use ($user) {
                    $q->where('role', 'doctor')
                        ->orWhere('role', 'admin');
                })
                    ->where('id', '!=', $user->id)
                    ->get(['id', 'fullname', 'role']);
            } else { // admin
                $contacts = AllUser::where('role', 'supplier')->get(['id', 'fullname', 'role', "email"]);
            }

            return response()->json([
                'success' => true,
                'data' => $contacts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // List all conversations for the logged-in user
    public function conversations(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $conversations = Conversation::where('participant_one', $userId)
                ->orWhere('participant_two', $userId)
                ->with([
                    'userOne:id,fullname,role',
                    'userTwo:id,fullname,role',
                    "userTwo.supplier:id,user_table_id,company_image_url,company_name",
                    "userTwo.doctor:id,user_table_id,profile_image_url"
                ])
                ->orderBy('last_message_at', 'desc')
                ->get()
                ->map(function ($conv) use ($userId) {
                    return [
                        'id'              => $conv->id,
                        'other_user'      => $conv->otherUser($userId),
                        'last_message_at' => $conv->last_message_at,

                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get messages inside a conversation (paginated)
    /*  public function messages(Request $request,AllUser $reciverId)
    {
        try {
            $userId= $request->user()->id;
           $user2=$reciverId->role=="doctor"?$reciverId->doctor:$reciverId->supplier;

            $conversation=Conversation::where("participant_one",$userId)->Where("participant_two",$user2->id)->first();
            $reciver=$reciverId->get("fullname,id")
            if(!$conversation)
                return response()->json([
                    'success' => true,
                    'data'=>[
                        "reciver"=>$user2->
                    ]
                ], 403);

            // Only participants can view messages
            if ($conversation->participant_one != $userId && $conversation->participant_two != $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized.',
                ], 403);
            }
            $reciverData=$conversation->userTwo();
            $messages = $conversation->messages()
                ->paginate(50);

            $data["reciver"]=$reciverData;
            $data["messages"]=$messages->items();



            return response()->json([
                'success' => true,
                'data' => $data,
                "last_page" => $messages->lastPage(),
                "per_page" => $messages->perPage(),
                "total" => $messages->total()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    } */
    // Get messages inside a conversation (paginated)
    public function messages(Request $request, Conversation $conversation)
    {
        try {
            $userId       = $request->user()->id;
            /*             $conversation = Conversation::findOrFail($conversationId);
 */
            // Only participants can view messages
            if ($conversation->participant_one != $userId && $conversation->participant_two != $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized.',
                ], 403);
            }

            $messages = $conversation->messages()
                ->with([
                    'sender:id,fullname,role',
                    'sender.supplier:id,user_table_id,company_image_url,company_name',
                    'sender.doctor:id,user_table_id,profile_image_url',
                    'product' => function ($query) {
                        $query->select('id', 'name')->with(['image' => function ($imageQuery) {
                            $imageQuery->select('product_id', 'image');
                        }]);
                    },
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => [
                        'id' => $conversation->id,
                        'participant_one' => $conversation->participant_one,
                        'participant_two' => $conversation->participant_two,

                    ],
                    'messages' => collect($messages)->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'conversation_id' => $message->conversation_id,
                            'sender_id' => $message->sender_id,
                            'message' => $message->body,
                            'product_id' => $message->product_id,
                            'product_name' => $message->product?->name,
                            'product_image' => $message->product?->image->first()?->image,
                            'sender' => $message->sender,
                            'read_at' => $message->read_at,
                            'created_at' => $message->created_at,
                            'updated_at' => $message->updated_at,
                        ];
                    })->values()->all(),
                ],
                'total' => $messages->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Send a message — this also triggers the Pusher broadcast
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:user,id',
            'message'        => 'required|string|max:5000',
            'product_id'    => 'nullable|exists:product,id',
        ]);
        try {

            $sender   = $request->user();
            $receiver = AllUser::findOrFail($request->receiver_id);

            if (!$this->canChat($sender, $receiver)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not allowed to chat with this user.',
                ], 403);
            }

            $conversation = Conversation::findOrCreate($sender->id, $receiver->id);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
                'body'            => $request->message,
                'product_id'      => $request->input('product_id'),
            ]);

            $conversation->update(['last_message_at' => now()]);

            broadcast(new NewMessageSent($message))->toOthers();

            $message->load([
                'sender',
                'product' => function ($query) {
                    $query->select('id', 'name')->with(['image' => function ($imageQuery) {
                        $imageQuery->select('product_id', 'image');
                    }]);
                },
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent.',
                'data' => [
                    'message' => $message,
                    'conversation' => $conversation,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*   public function startChat(Request $request, Supplier $supplier)
    {
        try {


            return response()->json([
                'success' => true,
                'message' => 'Supplier returned successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    } */
    public function markAsRead(Request $request, $conversationId)
    {
        try {
            $userId = $request->user()->id;

            Message::where('conversation_id', $conversationId)
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Marked as read.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Role-based chat permissions
    private function canChat(AllUser $sender, AllUser $receiver): bool
    {
        if ($sender->isDoctor() && $receiver->isSupplier()) return true;
        if ($sender->isSupplier() && $receiver->isDoctor()) return true;
        if ($sender->isSupplier() && $receiver->isAdmin())    return true;
        if ($sender->isAdmin()    && $receiver->isSupplier()) return true;

        return false;
    }
}
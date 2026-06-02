<?php

namespace App\Http\Controllers\Api;

use App\Services\ChatStore;
use Illuminate\Http\Request;

class ChatApiController extends BaseApiController
{
    public function storeChat(Request $request, ChatStore $chatStore)
    {
        $data = $request->validate([
            'sender_id' => 'required',
            'sender_name' => 'required',
            'sender_role' => 'required|in:admin,civitas',
            'receiver_id' => 'required',
            'receiver_name' => 'required',
            'body' => 'required',
            'sender_identifier' => 'nullable',
            'receiver_identifier' => 'nullable',
            'admin_username' => 'nullable',
            'campus_key' => 'nullable',
        ]);

        $receiverRole = $request->input('receiver_role', 'admin');
        $data['receiver_role'] = $receiverRole;

        $data['admin_username'] = $data['admin_username']
            ?? ($data['sender_role'] === 'admin' ? $data['sender_id'] : $data['receiver_id']);
        $data['campus_key'] = $data['campus_key'] ?? $data['admin_username'];

        if ($data['sender_role'] === 'civitas' && $receiverRole !== 'civitas') {
            $user = $this->users()->findOne([
                '$or' => [
                    ['username' => $data['sender_id']],
                    ['nim' => $data['sender_id']],
                    ['identifier' => $data['sender_id']],
                ],
            ]);

            if ($user) {
                $userData = json_decode(json_encode($user), true);
                $data['admin_username'] = $userData['admin_username'] ?? $data['admin_username'];
                $data['campus_key'] = $userData['campus_key'] ?? $userData['kode_kampus'] ?? $data['campus_key'];
                $data['receiver_id'] = $data['admin_username'];
            }
        } else if ($data['sender_role'] === 'civitas' && $receiverRole === 'civitas') {
            $user = $this->users()->findOne([
                '$or' => [
                    ['username' => $data['sender_id']],
                    ['nim' => $data['sender_id']],
                    ['identifier' => $data['sender_id']],
                ],
            ]);
            if ($user) {
                $userData = json_decode(json_encode($user), true);
                $data['campus_key'] = $userData['campus_key'] ?? $userData['kode_kampus'] ?? $data['campus_key'];
            }
        }

        return response()->json(['message' => $chatStore->send($data)], 201);
    }

    public function chatThread(Request $request, string $participantId, ChatStore $chatStore)
    {
        $adminUsername = $request->query('admin_username');
        $campusKey = $request->query('campus_key');
        $user = $this->users()->findOne([
            '$or' => [
                ['username' => $participantId],
                ['nim' => $participantId],
                ['identifier' => $participantId],
            ],
        ]);

        if ($user) {
            $userData = json_decode(json_encode($user), true);
            $adminUsername = $userData['admin_username'] ?? $adminUsername;
            $campusKey = $userData['campus_key'] ?? $userData['kode_kampus'] ?? $campusKey;
        }

        return response()->json([
            'thread' => $chatStore->thread($participantId, $adminUsername, $campusKey),
        ]);
    }

    public function civitasThreads(Request $request, string $nim, ChatStore $chatStore)
    {
        $user = $this->users()->findOne([
            '$or' => [
                ['username' => $nim],
                ['nim' => $nim],
                ['identifier' => $nim],
            ],
        ]);

        $campusKey = 'admin1';
        if ($user) {
            $userData = json_decode(json_encode($user), true);
            $campusKey = $userData['campus_key'] ?? $userData['kode_kampus'] ?? $campusKey;
        }

        return response()->json([
            'threads' => $chatStore->civitasThreads($nim, $campusKey),
        ]);
    }
}

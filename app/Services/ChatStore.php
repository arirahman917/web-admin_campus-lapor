<?php

namespace App\Services;

use Illuminate\Support\Str;
use MongoDB\Client;
use MongoDB\Collection;

class ChatStore
{
    private Collection $collection;
    private $db;

    public function __construct()
    {
        $client = new Client(config('database.connections.mongodb.dsn'));
        $this->db = $client->selectDatabase(config('database.connections.mongodb.database'));
        $this->collection = $this->db->selectCollection('chat_threads');
    }

    private function scopeFilter(?string $adminUsername = null, ?string $campusKey = null, array $filter = []): array
    {
        if ($adminUsername !== null && trim($adminUsername) !== '') {
            $filter['admin_username'] = $adminUsername;
        }

        if ($campusKey !== null && trim($campusKey) !== '') {
            $filter['campus_key'] = $campusKey;
        }

        return $filter;
    }

    public function threads(?array $participantIds = null, ?string $adminUsername = null, ?string $campusKey = null): array
    {
        $filter = $this->scopeFilter($adminUsername, $campusKey);
        if (is_array($participantIds)) {
            $filter['participant_id'] = ['$in' => $participantIds];
        }

        $threads = $this->collection
            ->find($filter, ['projection' => ['_id' => 0], 'sort' => ['last_at' => -1]])
            ->toArray();

        return array_map(fn ($thread) => json_decode(json_encode($thread), true), $threads);
    }

    public function unreadForAdmin(?array $participantIds = null, ?string $adminUsername = null, ?string $campusKey = null): int
    {
        return collect($this->threads($participantIds, $adminUsername, $campusKey))
            ->filter(fn ($thread) => collect($thread['messages'] ?? [])->contains(
                fn ($message) => ($message['sender_role'] ?? '') === 'civitas' && empty($message['read_by_admin'])
            ))
            ->count();
    }

    public function markAdminRead(string $participantId, ?string $adminUsername = null, ?string $campusKey = null): void
    {
        $thread = $this->thread($participantId, $adminUsername, $campusKey);
        if (! $thread) {
            return;
        }

        foreach ($thread['messages'] as &$message) {
            if (($message['sender_role'] ?? '') === 'civitas') {
                $message['read_by_admin'] = true;
            }
        }

        $this->collection->updateOne(
            $this->scopeFilter($adminUsername, $campusKey, ['participant_id' => $participantId]),
            ['$set' => ['messages' => $thread['messages']]]
        );
    }

    public function thread(string $participantId, ?string $adminUsername = null, ?string $campusKey = null): ?array
    {
        $thread = $this->collection->findOne(
            $this->scopeFilter($adminUsername, $campusKey, ['participant_id' => $participantId]),
            ['projection' => ['_id' => 0]]
        );

        return $thread ? json_decode(json_encode($thread), true) : null;
    }

    public function civitasThreads(string $nim, string $campusKey): array
    {
        $threads = $this->collection->find([
            'campus_key' => $campusKey,
            '$or' => [
                ['participant_id' => $nim],
                ['peer_a_id' => $nim],
                ['peer_b_id' => $nim],
            ]
        ], ['projection' => ['_id' => 0], 'sort' => ['last_at' => -1]])->toArray();

        $threadList = array_map(fn ($thread) => json_decode(json_encode($thread), true), $threads);
        $usersColl = $this->db->selectCollection('users');

        foreach ($threadList as &$t) {
            $peerId = null;
            if (isset($t['peer_a_id']) && isset($t['peer_b_id'])) {
                $peerId = $t['peer_a_id'] === $nim ? $t['peer_b_id'] : $t['peer_a_id'];
            }

            if ($peerId) {
                $peerUser = $usersColl->findOne([
                    'username' => $peerId,
                    'role' => 'civitas',
                    'status' => ['$in' => ['Aktif', 'aktif']],
                ]);
                if ($peerUser && isset($peerUser['profile_photo'])) {
                    $t['peer_profile_photo'] = $peerUser['profile_photo'];
                }
            } else {
                $adminId = $t['admin_username'] ?? 'admin1';
                $adminUser = $usersColl->findOne([
                    'username' => $adminId,
                    'role' => 'admin',
                ]);
                if ($adminUser && isset($adminUser['profile_photo'])) {
                    $t['peer_profile_photo'] = $adminUser['profile_photo'];
                }
            }
        }

        return $threadList;
    }

    public function send(array $payload): array
    {
        $isCivitasToCivitas = ($payload['receiver_role'] ?? '') === 'civitas';

        if ($isCivitasToCivitas) {
            $ids = [$payload['sender_id'], $payload['receiver_id']];
            sort($ids);
            $participantId = 'peer_' . $ids[0] . '_' . $ids[1];
            $adminUsername = 'civitas_peer';
            $campusKey = $payload['campus_key'] ?? 'civitas_peer';
        } else {
            $participantId = $payload['sender_role'] === 'admin'
                ? $payload['receiver_id']
                : $payload['sender_id'];
            $adminUsername = $payload['admin_username']
                ?? ($payload['sender_role'] === 'admin' ? $payload['sender_id'] : $payload['receiver_id']);
            $campusKey = $payload['campus_key'] ?? $adminUsername;
        }

        $message = [
            'id' => (string) Str::uuid(),
            'sender_id' => $payload['sender_id'],
            'sender_name' => $payload['sender_name'],
            'sender_role' => $payload['sender_role'],
            'receiver_id' => $payload['receiver_id'],
            'receiver_name' => $payload['receiver_name'],
            'body' => $payload['body'],
            'read_by_admin' => $payload['sender_role'] === 'admin',
            'created_at' => now()->toIso8601String(),
        ];

        $filter = $isCivitasToCivitas 
            ? ['participant_id' => $participantId, 'campus_key' => $campusKey]
            : $this->scopeFilter($adminUsername, $campusKey, ['participant_id' => $participantId]);

        $setOnInsert = $isCivitasToCivitas
            ? [
                'participant_id' => $participantId,
                'participant_name' => $payload['sender_name'] . ' & ' . $payload['receiver_name'],
                'participant_role' => 'civitas_peer',
                'participant_identifier' => $participantId,
                'peer_a_id' => $payload['sender_id'],
                'peer_a_name' => $payload['sender_name'],
                'peer_b_id' => $payload['receiver_id'],
                'peer_b_name' => $payload['receiver_name'],
              ]
            : [
                'participant_id' => $participantId,
                'participant_name' => $payload['sender_role'] === 'admin' ? $payload['receiver_name'] : $payload['sender_name'],
                'participant_role' => 'civitas',
                'participant_identifier' => $payload['sender_role'] === 'admin' ? ($payload['receiver_identifier'] ?? $participantId) : ($payload['sender_identifier'] ?? $participantId),
              ];

        $this->collection->updateOne(
            $filter,
            [
                '$setOnInsert' => $setOnInsert,
                '$push' => ['messages' => $message],
                '$set' => [
                    'admin_username' => $adminUsername,
                    'campus_key' => $campusKey,
                    'last_message' => $message['body'],
                    'last_at' => $message['created_at'],
                ],
            ],
            ['upsert' => true]
        );

        // Buat notifikasi otomatis ke penerima jika penerima adalah civitas
        $receiverRole = $payload['receiver_role'] ?? (($payload['sender_role'] ?? '') === 'admin' ? 'civitas' : 'admin');
        if ($receiverRole === 'civitas') {
            \App\Models\Notification::create([
                'user_id' => $payload['receiver_id'],
                'title' => ($payload['sender_role'] ?? '') === 'admin' ? 'Pesan Baru dari Admin Kampus' : 'Pesan Baru',
                'body' => ($payload['sender_role'] ?? '') === 'admin' 
                    ? \Illuminate\Support\Str::limit($payload['body'], 40)
                    : 'Anda menerima pesan baru dari ' . $payload['sender_name'] . ': "' . \Illuminate\Support\Str::limit($payload['body'], 40) . '"',
                'is_read' => false,
                'type' => 'chat',
                'sender_id' => $payload['sender_id'] ?? 'admin1',
                'sender_name' => $payload['sender_name'] ?? 'Admin Kampus',
                'sender_role' => $payload['sender_role'] ?? 'civitas',
                'created_at' => now()->toIso8601String(),
            ]);
        }

        return $message;
    }
}

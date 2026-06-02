<?php
namespace App\Http\Controllers;

use App\Services\ChatStore;
use App\Services\CampusDataStore;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private function dashboardData(): array
    {
        return [
            'barangHilang' => [],
            'barangDitemukan' => [],
            'fasilitasRusak' => [],
            'fasilitasDiperbaiki' => [],
            'users' => [],
            'locations' => [],
            'adminCandidates' => [],
            'messages' => [],
            'chartData' => collect(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'])
                ->map(fn ($month) => ['month' => $month, 'barangHilang' => 0, 'fasilitasRusak' => 0])
                ->all(),
        ];
    }

    private function usesDefaultCampusData(): bool
    {
        return false;
    }

    private function campusKey(): string
    {
        return session('auth_kode_kampus')
            ?: session('auth_kampus')
            ?: session('auth_username', 'default');
    }

    private function adminUsername(): string
    {
        return session('auth_username', 'admin1');
    }

    public function pesan(ChatStore $chatStore, CampusDataStore $campusData)
    {
        $d = $this->dashboardData();
        $storedUsers = collect($campusData->civitasUsers($this->campusKey()));
        $allUsers = $this->usesDefaultCampusData()
            ? collect($d['users'])->concat($storedUsers)
            : $storedUsers;
        $threads = $chatStore->threads(null, $this->adminUsername(), $this->campusKey());
        $users = collect($threads)
            ->filter(fn ($thread) => ! empty($thread['messages'] ?? []))
            ->map(function ($thread) use ($allUsers) {
                $participantId = $thread['participant_id'] ?? '';
                $user = $allUsers->firstWhere('nim', $participantId);

                return $user ?? [
                    'id' => $participantId,
                    'nama' => $thread['participant_name'] ?? 'Civitas',
                    'nim' => $participantId,
                    'email' => '-',
                    'role' => $thread['participant_role'] ?? 'Mahasiswa',
                    'status' => 'Aktif',
                ];
            })
            ->values();

        return view('pages.pesan', ['users'=>$users,'messages'=>$d['messages'], 'threads' => $threads]);
    }

    public function kirimPesan(Request $r, ChatStore $chatStore, CampusDataStore $campusData)
    {
        $r->validate(['penerima'=>'required','isi'=>'required', 'receiver_id' => 'nullable']);
        $d = $this->dashboardData();
        $storedUsers = collect($campusData->civitasUsers($this->campusKey()));
        $users = $this->usesDefaultCampusData()
            ? collect($d['users'])->concat($storedUsers)
            : $storedUsers;
        $user = $users->firstWhere('nim', $r->receiver_id)
            ?? $users->firstWhere('nama', $r->penerima)
            ?? [
                'nim' => $r->receiver_id,
                'nama' => $r->penerima,
            ];

        $sentMessage = null;
        if (! empty($user['nim'])) {
            $sentMessage = $chatStore->send([
                'sender_id' => session('auth_username', 'admin1'),
                'sender_name' => session('auth_name', 'Admin Kampus'),
                'sender_role' => 'admin',
                'admin_username' => $this->adminUsername(),
                'campus_key' => $this->campusKey(),
                'receiver_id' => $user['nim'],
                'receiver_identifier' => $user['nim'],
                'receiver_name' => $user['nama'],
                'body' => $r->isi,
            ]);
        }

        if ($r->expectsJson()) {
            if (! $sentMessage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penerima chat tidak ditemukan.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'chat_message' => $sentMessage,
                'message' => 'Pesan berhasil dikirim ke '.$r->penerima.'.',
            ]);
        }

        return redirect()->route('pesan')->with('success', 'Pesan berhasil dikirim ke '.$r->penerima.'.');
    }

    public function chatSend(Request $r, ChatStore $chatStore)
    {
        $data = $r->validate([
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

        $data['admin_username'] = $data['admin_username']
            ?? ($data['sender_role'] === 'admin' ? $data['sender_id'] : $data['receiver_id']);
        $data['campus_key'] = $data['campus_key'] ?? $data['admin_username'];

        if ($data['sender_role'] === 'civitas') {
            $user = (new \MongoDB\Client(config('database.connections.mongodb.dsn')))
                ->selectDatabase(config('database.connections.mongodb.database'))
                ->selectCollection('users')
                ->findOne([
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
        }

        return response()
            ->json(['message' => $chatStore->send($data)])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function chatThread(Request $request, string $participantId, ChatStore $chatStore)
    {
        $adminUsername = $request->query('admin_username', $this->adminUsername());
        $campusKey = $request->query('campus_key', $this->campusKey());

        return response()
            ->json(['thread' => $chatStore->thread($participantId, $adminUsername, $campusKey)])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function chatMarkRead(string $participantId, ChatStore $chatStore)
    {
        $chatStore->markAdminRead($participantId, $this->adminUsername(), $this->campusKey());

        return response()
            ->json(['success' => true, 'unread' => $chatStore->unreadForAdmin(null, $this->adminUsername(), $this->campusKey())])
            ->header('Access-Control-Allow-Origin', '*');
    }
}

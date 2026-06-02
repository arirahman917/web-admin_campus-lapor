<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;

class AdminApplicationStore
{
    private Collection $applications;

    private Collection $users;

    public function __construct()
    {
        $client = new Client(config('database.connections.mongodb.dsn'));
        $database = $client->selectDatabase(config('database.connections.mongodb.database'));
        $this->applications = $database->selectCollection('admin_applications');
        $this->users = $database->selectCollection('users');
    }

    public function all(): array
    {
        $items = $this->applications
            ->find([], ['projection' => ['password' => 0], 'sort' => ['created_at' => -1]])
            ->toArray();

        return array_map(fn ($item) => $this->normalize($item), $items);
    }

    public function approvedAdmins(): array
    {
        $items = $this->users
            ->find(
                ['role' => 'admin', 'status' => 'aktif'],
                ['projection' => ['password' => 0], 'sort' => ['created_at' => -1, 'name' => 1]]
            )
            ->toArray();

        return array_map(fn ($item) => $this->normalize($item), $items);
    }

    public function activeIdentityExists(string $username, string $email): bool
    {
        $existingUser = $this->users->findOne([
            '$or' => [
                ['username' => $username],
                ['email' => $email],
            ],
        ]);

        $existingApplication = $this->applications->findOne([
            'status' => ['$nin' => ['Ditolak', 'Banned']],
            '$or' => [
                ['username' => $username],
                ['email' => $email],
            ],
        ]);

        return (bool) ($existingUser || $existingApplication);
    }

    public function create(array $data): string
    {
        $insert = $this->applications->insertOne([
            'nama' => $data['nama'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nidn' => $data['nidn'],
            'phone' => $data['phone'] ?? null,
            'kampus' => $data['kampus'],
            'kode_kampus' => $data['kode_kampus'] ?? null,
            'alamat_kampus' => $data['alamat_kampus'] ?? null,
            'unit' => $data['unit'],
            'role' => 'Calon Admin',
            'status' => 'Menunggu',
            'alasan' => $data['alasan'],
            'surat_tugas_path' => $data['surat_tugas_path'] ?? null,
            'surat_tugas_nama' => $data['surat_tugas_nama'] ?? null,
            'surat_tugas_mime' => $data['surat_tugas_mime'] ?? null,
            'surat_tugas_size' => $data['surat_tugas_size'] ?? null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        return (string) $insert->getInsertedId();
    }

    public function updateStatus(string $id, string $status): bool
    {
        if (! preg_match('/^[a-f\d]{24}$/i', $id)) {
            return false;
        }

        $application = $this->applications->findOne(['_id' => new ObjectId($id)]);
        if (! $application) {
            return false;
        }

        if (($application['status'] ?? null) === 'Banned' && $status !== 'Banned') {
            return false;
        }

        $this->applications->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => ['status' => $status, 'updated_at' => now()->toIso8601String()]]
        );

        if ($status === 'Disetujui') {
            $this->users->updateOne(
                ['username' => $application['username']],
                ['$set' => [
                    'name' => $application['nama'],
                    'username' => $application['username'],
                    'email' => $application['email'],
                    'password' => $application['password'],
                    'role' => 'admin',
                    'status' => 'aktif',
                    'identifier' => $application['nidn'],
                    'unit' => $application['unit'],
                    'kampus' => $application['kampus'],
                    'kode_kampus' => $application['kode_kampus'] ?? null,
                    'alamat_kampus' => $application['alamat_kampus'] ?? null,
                    'phone' => $application['phone'] ?? null,
                    'updated_at' => now()->toIso8601String(),
                ], '$setOnInsert' => ['created_at' => now()->toIso8601String()]],
                ['upsert' => true]
            );
        } elseif ($status === 'Ditolak') {
            $this->users->updateOne(
                ['username' => $application['username'], 'role' => 'admin'],
                ['$set' => ['status' => 'tidak aktif', 'updated_at' => now()->toIso8601String()]]
            );
        } elseif ($status === 'Banned') {
            $this->users->deleteOne(['username' => $application['username'], 'role' => 'admin']);
        }

        return true;
    }

    public function document(string $id): ?array
    {
        if (! preg_match('/^[a-f\d]{24}$/i', $id)) {
            return null;
        }

        $application = $this->applications->findOne(
            ['_id' => new ObjectId($id)],
            ['projection' => [
                'surat_tugas_path' => 1,
                'surat_tugas_nama' => 1,
                'surat_tugas_mime' => 1,
            ]]
        );

        if (! $application || empty($application['surat_tugas_path'])) {
            return null;
        }

        $item = $application->getArrayCopy();

        return [
            'path' => $item['surat_tugas_path'],
            'name' => $item['surat_tugas_nama'] ?? 'surat-tugas',
            'mime' => $item['surat_tugas_mime'] ?? 'application/octet-stream',
        ];
    }

    private function normalize(object|array $item): array
    {
        $array = json_decode(json_encode($item), true);
        $array['id'] = $array['_id']['$oid'] ?? (string) ($array['_id'] ?? '');

        return $array;
    }
}

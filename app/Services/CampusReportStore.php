<?php

namespace App\Services;

use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;

class CampusReportStore
{
    private Collection $reports;
    private Collection $users;

    public function __construct()
    {
        $client = new Client(config('database.connections.mongodb.dsn'));
        $database = $client->selectDatabase(config('database.connections.mongodb.database'));
        $this->reports = $database->selectCollection('campus_reports');
        $this->users = $database->selectCollection('users');
    }

    public function createFromMobile(array $data, ?string $overrideCampusKey = null): array
    {
        $user = $this->users->findOne([
            '$or' => [
                ['username' => $data['reporter_id']],
                ['identifier' => $data['reporter_id']],
                ['nim' => $data['reporter_id']],
            ],
            'role' => 'civitas',
        ]);

        $campusKey = $overrideCampusKey
            ?? ($user['campus_key'] ?? null)
            ?? ($user['kode_kampus'] ?? null)
            ?? 'unknown';
        $report = [
            'campus_key' => $campusKey,
            'category' => $data['category'],
            'title' => $data['title'],
            'location' => $data['location'],
            'tag' => $data['tag'] ?? null,
            'status' => $data['category'] === 'Fasilitas Rusak' ? 'Dilaporkan' : 'Aktif',
            'description' => $data['description'] ?? '',
            'reporter_id' => $data['reporter_id'],
            'reporter_name' => $user['name'] ?? $data['reporter_name'],
            'photo_data' => $data['photo_data'] ?? null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $result = $this->reports->insertOne($report);
        $report['id'] = (string) $result->getInsertedId();

        return $report;
    }

    public function forCampus(string $campusKey): array
    {
        $items = $this->reports
            ->find(['campus_key' => $campusKey], ['sort' => ['created_at' => -1]])
            ->toArray();

        // Eager load reporter profile photos in ONE single batch query!
        $reporterIds = array_values(array_filter(array_unique(array_column($items, 'reporter_id'))));
        $usersMap = [];
        if (!empty($reporterIds)) {
            $users = $this->users->find([
                '$or' => [
                    ['username' => ['$in' => $reporterIds]],
                    ['nim' => ['$in' => $reporterIds]],
                    ['identifier' => ['$in' => $reporterIds]],
                ],
                'role' => 'civitas',
                'status' => ['$in' => ['Aktif', 'aktif']],
            ])->toArray();
            foreach ($users as $u) {
                $uArr = json_decode(json_encode($u), true);
                $photo = $uArr['profile_photo'] ?? null;
                if ($uArr['username'] ?? null) $usersMap[$uArr['username']] = $photo;
                if ($uArr['nim'] ?? null) $usersMap[$uArr['nim']] = $photo;
                if ($uArr['identifier'] ?? null) $usersMap[$uArr['identifier']] = $photo;
            }
        }

        return array_map(function ($item) use ($usersMap) {
            $array = $this->normalize($item);
            $reporterId = $array['reporter_id'] ?? null;
            $array['reporter_photo'] = ($reporterId && isset($usersMap[$reporterId])) ? $usersMap[$reporterId] : null;
            return $array;
        }, $items);
    }

    public function forReporter(string $reporterId): array
    {
        $items = $this->reports
            ->find(['reporter_id' => $reporterId], ['sort' => ['created_at' => -1]])
            ->toArray();

        // Eager load reporter profile photo in ONE single query!
        $usersMap = [];
        if ($reporterId) {
            $user = $this->users->findOne([
                '$or' => [
                    ['username' => $reporterId],
                    ['nim' => $reporterId],
                    ['identifier' => $reporterId],
                ],
                'role' => 'civitas',
                'status' => ['$in' => ['Aktif', 'aktif']],
            ]);
            if ($user) {
                $uArr = json_decode(json_encode($user), true);
                $photo = $uArr['profile_photo'] ?? null;
                if ($uArr['username'] ?? null) $usersMap[$uArr['username']] = $photo;
                if ($uArr['nim'] ?? null) $usersMap[$uArr['nim']] = $photo;
                if ($uArr['identifier'] ?? null) $usersMap[$uArr['identifier']] = $photo;
            }
        }

        return array_map(function ($item) use ($usersMap) {
            $array = $this->normalize($item);
            $reporterId = $array['reporter_id'] ?? null;
            $array['reporter_photo'] = ($reporterId && isset($usersMap[$reporterId])) ? $usersMap[$reporterId] : null;
            return $array;
        }, $items);
    }

    public function updateStatus(string $campusKey, string $id, string $status): bool
    {
        if (! preg_match('/^[a-f\d]{24}$/i', $id)) {
            return false;
        }

        $changes = ['$set' => ['status' => $status, 'updated_at' => now()->toIso8601String()]];
        $result = $this->reports->updateOne(
            ['_id' => new ObjectId($id), 'campus_key' => $campusKey],
            $changes
        );

        if ($result->getMatchedCount() > 0) {
            return true;
        }

        $result = $this->reports->updateOne(['_id' => new ObjectId($id)], $changes);

        return $result->getMatchedCount() > 0;
    }

    private function normalize(object|array $item): array
    {
        $array = json_decode(json_encode($item), true);
        $array['id'] = $array['_id']['$oid'] ?? (string) ($array['_id'] ?? '');

        return $array;
    }
}

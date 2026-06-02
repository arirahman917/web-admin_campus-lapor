<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use MongoDB\Client;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mongo:sync-to-atlas {--drop : Kosongkan collection Atlas sebelum copy data lokal}', function () {
    $sourceUri = env('LOCAL_MONGODB_URI', 'mongodb://127.0.0.1:27017');
    $sourceDatabase = env('LOCAL_MONGODB_DATABASE', 'kampus_lapor');
    $targetUri = config('database.connections.mongodb.dsn');
    $targetDatabase = config('database.connections.mongodb.database');

    if ($sourceUri === $targetUri && $sourceDatabase === $targetDatabase) {
        $this->error('Source dan target masih sama. Isi DB_URI Atlas dulu di .env.');
        return self::FAILURE;
    }

    $source = (new Client($sourceUri))->selectDatabase($sourceDatabase);
    $target = (new Client($targetUri))->selectDatabase($targetDatabase);

    $collections = [];
    foreach ($source->listCollections() as $collection) {
        $collections[] = $collection->getName();
    }

    if ($collections === []) {
        $this->warn("Tidak ada collection di database lokal {$sourceDatabase}.");
        return self::SUCCESS;
    }

    $this->info("Sync {$sourceDatabase} -> {$targetDatabase}");

    foreach ($collections as $collectionName) {
        if ($this->option('drop')) {
            $target->dropCollection($collectionName);
        }

        $sourceCollection = $source->selectCollection($collectionName);
        $targetCollection = $target->selectCollection($collectionName);
        $documents = $sourceCollection->find()->toArray();

        if ($documents === []) {
            $target->createCollection($collectionName);
            $this->line("- {$collectionName}: collection kosong dibuat");
            continue;
        }

        $targetCollection->insertMany($documents);
        $this->line("- {$collectionName}: ".count($documents).' dokumen disalin');
    }

    $this->info('Selesai sync MongoDB lokal ke Atlas.');
    return self::SUCCESS;
})->purpose('Copy semua collection kampus_lapor dari MongoDB lokal ke MongoDB Atlas');

Artisan::command('mongo:scope-chat-threads', function () {
    $database = (new Client(config('database.connections.mongodb.dsn')))
        ->selectDatabase(config('database.connections.mongodb.database'));
    $threads = $database->selectCollection('chat_threads');
    $users = $database->selectCollection('users');

    $updated = 0;
    foreach ($threads->find(['admin_username' => ['$exists' => false]]) as $thread) {
        $threadData = json_decode(json_encode($thread), true);
        $participantId = $threadData['participant_id'] ?? null;
        if (! $participantId) {
            continue;
        }

        $user = $users->findOne([
            '$or' => [
                ['username' => $participantId],
                ['nim' => $participantId],
                ['identifier' => $participantId],
            ],
        ]);

        $userData = $user ? json_decode(json_encode($user), true) : [];
        $adminUsername = $userData['admin_username'] ?? null;
        $campusKey = $userData['campus_key'] ?? $userData['kode_kampus'] ?? null;

        if (! $adminUsername) {
            foreach ($threadData['messages'] ?? [] as $message) {
                if (($message['sender_role'] ?? '') === 'admin') {
                    $adminUsername = $message['sender_id'] ?? $adminUsername;
                    break;
                }
                if (($message['sender_role'] ?? '') === 'civitas') {
                    $adminUsername = $message['receiver_id'] ?? $adminUsername;
                    break;
                }
            }
        }

        if (! $campusKey) {
            $admin = $adminUsername
                ? $users->findOne(['username' => $adminUsername, 'role' => 'admin'])
                : null;
            $adminData = $admin ? json_decode(json_encode($admin), true) : [];
            $campusKey = $adminData['campus_key'] ?? $adminData['kode_kampus'] ?? $adminUsername;
        }

        if (! $adminUsername || ! $campusKey) {
            $this->warn("- {$participantId}: dilewati, admin kampus tidak ditemukan");
            continue;
        }

        $threads->updateOne(
            ['_id' => $thread['_id']],
            ['$set' => [
                'admin_username' => $adminUsername,
                'campus_key' => $campusKey,
            ]]
        );
        $updated++;
        $this->line("- {$participantId}: {$adminUsername} / {$campusKey}");
    }

    $this->info("Selesai. {$updated} thread chat diberi scope admin/kampus.");
    return self::SUCCESS;
})->purpose('Memberi scope admin_username dan campus_key untuk thread chat lama');

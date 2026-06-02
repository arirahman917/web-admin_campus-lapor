<?php

namespace App\Http\Controllers\Api;

use App\Services\AdminApplicationStore;
use App\Services\CampusDataStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\ApiToken;
use Illuminate\Support\Str;

class AuthApiController extends BaseApiController
{
    public function loginSuperAdmin(Request $request)
    {
        return $this->loginRole($request, 'superadmin');
    }

    public function loginAdminKampus(Request $request)
    {
        return $this->loginRole($request, 'admin');
    }

    public function loginCivitas(Request $request, CampusDataStore $campusData)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);
        $user = $this->users()->findOne([
            'username' => $data['username'],
            'role' => 'civitas',
            'status' => ['$in' => ['Aktif', 'aktif']],
        ]);

        if (! $user || ! Hash::check($data['password'], $user['password'] ?? '')) {
            return response()->json(['message' => 'Username atau password tidak sesuai.'], 422);
        }

        $payload = $this->publicUser($user);
        $payload['locations'] = $campusData->locations($payload['campus_key'] ?? $payload['kode_kampus'] ?? '');

        $token = Str::random(60);
        ApiToken::create([
            'user_id' => (string) $user['_id'],
            'token' => hash('sha256', $token),
            'created_at' => now()->toIso8601String(),
        ]);

        $payload['token'] = $token;

        return response()->json(['message' => 'Login berhasil.', 'user' => $payload, 'token' => $token]);
    }

    public function registerKampus(Request $request, AdminApplicationStore $applications)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:120',
            'username' => 'required|string|max:60',
            'email' => 'required|email|max:160',
            'password' => 'required|string|min:6',
            'nidn' => 'required|string|max:60',
            'phone' => 'required|string|max:40',
            'kampus' => 'required|string|max:160',
            'kode_kampus' => 'required|string|max:80',
            'alamat_kampus' => 'required|string|max:220',
            'unit' => 'required|string|max:120',
            'alasan' => 'required|string|max:500',
        ]);

        if ($applications->activeIdentityExists($data['username'], $data['email'])) {
            return response()->json([
                'message' => 'Username atau email sudah digunakan. Coba pakai data admin kampus yang lain.',
            ], 422);
        }

        return response()->json(['id' => $applications->create($data), 'status' => 'Menunggu'], 201);
    }

    public function registerCivitas(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'nim' => 'required|string|max:60',
            'email' => 'required|email|max:160',
            'password' => 'required|string|min:6',
        ]);

        $domain = strtolower(substr(strrchr($data['email'], '@') ?: '', 1));
        $admin = $this->findAdminByDomain($domain);
        if (! $admin) {
            return response()->json(['message' => "Domain @{$domain} belum punya admin aktif."], 422);
        }

        $user = [
            'name' => $data['name'],
            'username' => $data['nim'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'civitas',
            'civitas_role' => 'Mahasiswa',
            'status' => 'Menunggu',
            'identifier' => $data['nim'],
            'nim' => $data['nim'],
            'kampus' => $admin['kampus'] ?? null,
            'kode_kampus' => $admin['kode_kampus'] ?? $domain,
            'campus_key' => $admin['kode_kampus'] ?? $domain,
            'admin_username' => $admin['username'],
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
        $this->users()->insertOne($user);

        return response()->json(['message' => 'Akun menunggu persetujuan admin kampus.', 'pending' => true, 'status' => 'Menunggu'], 201);
    }

    private function loginRole(Request $request, string $role)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);
        $user = $this->users()->findOne(['username' => $data['username'], 'role' => $role, 'status' => 'aktif']);

        if (! $user || ! Hash::check($data['password'], $user['password'] ?? '')) {
            return response()->json(['message' => 'Username atau password tidak sesuai.'], 422);
        }

        $token = Str::random(60);
        ApiToken::create([
            'user_id' => (string) $user['_id'],
            'token' => hash('sha256', $token),
            'created_at' => now()->toIso8601String(),
        ]);

        $userPayload = $this->publicUser($user);
        $userPayload['token'] = $token;

        return response()->json(['user' => $userPayload, 'token' => $token]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user() ?: $request->auth_user;
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'nim' => 'required|string|max:60',
            'profile_photo' => 'nullable|string',
        ]);

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'nim' => $data['nim'],
            'username' => $data['nim'],
            'identifier' => $data['nim'],
            'updated_at' => now()->toIso8601String(),
        ];

        if (array_key_exists('profile_photo', $data)) {
            $updateData['profile_photo'] = $data['profile_photo'];
        }

        // Menggunakan forceFill pada Eloquent Model agar data non-fillable (seperti profile_photo) tetap dapat diupdate
        $user->forceFill($updateData)->save();

        // Ambil data terbaru dalam bentuk raw document menggunakan BSON ObjectId agar kompatibel dengan publicUser()
        $updatedUser = $this->users()->findOne(['_id' => new \MongoDB\BSON\ObjectId($user->id)]);
        $payload = $this->publicUser($updatedUser);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $payload
        ]);
    }

    private function findAdminByDomain(string $domain): ?array
    {
        foreach ($this->users()->find(['role' => 'admin', 'status' => 'aktif']) as $admin) {
            $item = $admin->getArrayCopy();
            $code = strtolower((string) ($item['kode_kampus'] ?? ''));
            if ($code === $domain || str_contains($code, $domain)) {
                return $item;
            }
        }

        return null;
    }
}

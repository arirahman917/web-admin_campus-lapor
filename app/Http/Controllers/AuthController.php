<?php

namespace App\Http\Controllers;

use App\Services\AdminApplicationStore;
use App\Services\CampusDataStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use MongoDB\Client;

class AuthController extends Controller
{
    public function login()
    {
        if (session('auth_role') === 'superadmin') {
            return redirect()->route('superadmin.seleksi-admin');
        }

        if (session('auth_role') === 'admin') {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function showAdminRegistration()
    {
        return view('auth.admin-register');
    }

    public function storeAdminRegistration(Request $request, AdminApplicationStore $applications)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['required', 'string', 'min:6'],
            'nidn' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:40'],
            'kampus' => ['required', 'string', 'max:160'],
            'kode_kampus' => ['required', 'string', 'max:80'],
            'alamat_kampus' => ['required', 'string', 'max:220'],
            'unit' => ['required', 'string', 'max:120'],
            'alasan' => ['required', 'string', 'max:500'],
            'surat_tugas' => ['required', 'file', 'max:10240'],
        ]);

        if ($this->usernameOrEmailTaken($data['username'], $data['email'])) {
            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Username atau email sudah digunakan. Coba pakai data admin kampus yang lain.');
        }

        $file = $request->file('surat_tugas');
        $data['surat_tugas_path'] = $file->store('admin-applications');
        $data['surat_tugas_nama'] = $file->getClientOriginalName();
        $data['surat_tugas_mime'] = $file->getClientMimeType();
        $data['surat_tugas_size'] = $file->getSize();

        $applications->create($data);

        return redirect()
            ->route('login')
            ->with('success', 'Pengajuan admin kampus berhasil dikirim. Tunggu superadmin menyetujui akun sebelum login.');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->findUser($credentials['username']);

        if (! $user || ! Hash::check($credentials['password'], $user['password'] ?? '')) {
            return back()
                ->withInput($request->only('username'))
                ->with('error', 'Username atau password tidak sesuai.');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'auth_user_id' => (string) $user['_id'],
            'auth_name' => $user['name'] ?? $user['username'],
            'auth_username' => $user['username'],
            'auth_email' => $user['email'],
            'auth_role' => $user['role'],
            'auth_kampus' => $user['kampus'] ?? null,
            'auth_kode_kampus' => $user['kode_kampus'] ?? null,
            'auth_phone' => $user['phone'] ?? null,
            'auth_alamat_kampus' => $user['alamat_kampus'] ?? null,
        ]);

        return $user['role'] === 'superadmin'
            ? redirect()->route('superadmin.seleksi-admin')
            : redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function mobileRegister(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nim' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $domain = strtolower(substr(strrchr($data['email'], '@') ?: '', 1));
        $admin = $this->findAdminByCampusDomain($domain);

        if (! $admin) {
            return response()
                ->json(['message' => "Domain email @{$domain} belum terhubung dengan admin kampus yang aktif."], 422)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $database = $this->database();
        $existing = $database->selectCollection('users')->findOne([
            '$or' => [
                ['email' => $data['email']],
                ['identifier' => $data['nim']],
                ['nim' => $data['nim']],
            ],
        ]);

        if ($existing) {
            return response()
                ->json(['message' => 'Email atau NIM/ID sudah terdaftar.'], 422)
                ->header('Access-Control-Allow-Origin', '*');
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

        $database->selectCollection('users')->insertOne($user);
        unset($user['password']);

        return response()
            ->json(['message' => 'Akun civitas berhasil dibuat dan menunggu persetujuan admin kampus.', 'pending' => true])
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function mobileLogin(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->database()->selectCollection('users')->findOne([
            'username' => $data['username'],
            'role' => 'civitas',
            'status' => ['$in' => ['Aktif', 'aktif']],
        ]);

        if (! $user || ! Hash::check($data['password'], $user['password'] ?? '')) {
            return response()
                ->json(['message' => 'Username atau password civitas tidak sesuai.'], 422)
                ->header('Access-Control-Allow-Origin', '*');
        }

        $payload = $user->getArrayCopy();
        $payload['id'] = (string) $payload['_id'];
        unset($payload['_id'], $payload['password']);
        $payload['locations'] = app(CampusDataStore::class)
            ->locations($payload['campus_key'] ?? $payload['kode_kampus'] ?? '');

        return response()
            ->json(['message' => 'Login berhasil.', 'user' => $payload])
            ->header('Access-Control-Allow-Origin', '*');
    }

    private function findUser(string $username): ?array
    {
        $user = $this->database()
            ->selectCollection('users')
            ->findOne([
                'username' => $username,
                'role' => ['$in' => ['admin', 'superadmin']],
                'status' => 'aktif',
            ]);

        return $user ? $user->getArrayCopy() : null;
    }

    private function findAdminByCampusDomain(string $domain): ?array
    {
        if ($domain === '') {
            return null;
        }

        $admins = $this->database()->selectCollection('users')->find([
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        foreach ($admins as $admin) {
            $item = $admin->getArrayCopy();
            $campusCode = strtolower((string) ($item['kode_kampus'] ?? ''));
            if ($campusCode === $domain || str_contains($campusCode, $domain)) {
                return $item;
            }
        }

        return null;
    }

    private function usernameOrEmailTaken(string $username, string $email): bool
    {
        $database = $this->database();

        $existingUser = $database->selectCollection('users')->findOne([
            '$or' => [
                ['username' => $username],
                ['email' => $email],
            ],
        ]);

        $existingApplication = $database->selectCollection('admin_applications')->findOne([
            'status' => ['$nin' => ['Ditolak', 'Banned']],
            '$or' => [
                ['username' => $username],
                ['email' => $email],
            ],
        ]);

        return (bool) ($existingUser || $existingApplication);
    }

    private function database()
    {
        return (new Client(config('database.connections.mongodb.dsn')))
            ->selectDatabase(config('database.connections.mongodb.database'));
    }
}

<?php
namespace App\Http\Controllers;

use App\Services\CampusDataStore;
use Illuminate\Http\Request;

class LocationController extends Controller
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

    public function manajemenKampus(CampusDataStore $campusData)
    {
        $d = $this->dashboardData();
        $storedUsers = collect($campusData->civitasUsers($this->campusKey()));
        $users = $this->usesDefaultCampusData()
            ? collect($d['users'])->concat($storedUsers)
            : $storedUsers;
        $bannedUsers = $users->where('status','Banned');
        $storedLocations = collect($campusData->locations($this->campusKey()));
        $locations = $this->usesDefaultCampusData()
            ? collect($d['locations'])->concat($storedLocations)
            : $storedLocations;
        $adminProfile = $campusData->adminByUsername(session('auth_username', 'admin1')) ?? [];
        $adminApplication = $campusData->adminApplicationByUsername(session('auth_username', 'admin1')) ?? [];
        $campusProfile = [
            'name' => session('auth_kampus') ?: 'Institut Pertanian Bogor',
            'code' => session('auth_kode_kampus') ?: 'IPB',
            'email' => session('auth_email', 'admin@apps.ipb.ac.id'),
            'address' => $adminProfile['alamat_kampus'] ?? $adminApplication['alamat_kampus'] ?? session('auth_alamat_kampus') ?? (session('auth_username') === 'admin1'
                ? 'Kampus IPB Dramaga, Jl. Raya Dramaga, Kabupaten Bogor, Jawa Barat 16680'
                : 'Belum diisi'),
            'emergency' => $adminProfile['phone'] ?? $adminApplication['phone'] ?? session('auth_phone') ?? (session('auth_username') === 'admin1' ? '+62 251 8622642, Ext: 112' : 'Belum diisi'),
        ];
        return view('pages.manajemen-kampus', compact('users','bannedUsers','locations','campusProfile'));
    }

    public function toggleUserStatus(Request $r, $id)
    {
        $status = $r->input('status');
        if (! in_array($status, ['Aktif', 'Banned', 'Menunggu'], true)) {
            $status = 'Aktif';
        }

        if (app(CampusDataStore::class)->setCivitasStatus($this->campusKey(), (string) $id, $status)) {
            return back()->with('success', "Status pengguna berhasil diubah menjadi {$status}.");
        }

        return back()->with('success', "Status pengguna ID {$id} berhasil diperbarui.");
    }

    public function simpanLokasi(Request $r)
    {
        $r->validate(['nama' => 'required|max:120', 'area' => 'required|max:120']);
        app(CampusDataStore::class)->addLocation($this->campusKey(), $r->only('nama', 'area'));

        return back()->with('success', "Lokasi {$r->nama} berhasil disimpan untuk pilihan civitas.");
    }

    public function hapusLokasi($id)
    {
        $deleted = app(CampusDataStore::class)->deleteLocation($this->campusKey(), (string) $id);

        return back()->with(
            $deleted ? 'success' : 'error',
            $deleted
                ? "Lokasi berhasil dihapus dari daftar pilihan civitas."
                : "Lokasi bawaan atau lokasi kampus lain tidak bisa dihapus dari akun ini."
        );
    }
}

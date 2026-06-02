<?php
namespace App\Http\Controllers;

use App\Services\AdminApplicationStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuperAdminController extends Controller
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

    private function campusKey(): string
    {
        return session('auth_kode_kampus')
            ?: session('auth_kampus')
            ?: session('auth_username', 'default');
    }

    public function seleksiAdmin(AdminApplicationStore $applications)
    {
        $candidates = $this->superadminCandidates($applications);
        $summary = [
            'menunggu' => $candidates->where('status', 'Menunggu')->count(),
            'disetujui' => $candidates->where('status', 'Disetujui')->count(),
            'ditolak' => $candidates->where('status', 'Ditolak')->count(),
            'banned' => $candidates->where('status', 'Banned')->count(),
        ];

        return view('pages.superadmin-seleksi-admin', compact('candidates', 'summary'));
    }

    public function adminAktif(AdminApplicationStore $applications)
    {
        $activeAdmins = $this->superadminActiveAdmins($applications);
        $summary = [
            'total' => $activeAdmins->count(),
            'kampus' => $activeAdmins->pluck('kampus')->unique()->count(),
            'aktif' => $activeAdmins->where('status', 'aktif')->count(),
        ];

        return view('pages.superadmin-admin-aktif', compact('activeAdmins', 'summary'));
    }

    public function dataKampus(AdminApplicationStore $applications)
    {
        $activeAdmins = $this->superadminActiveAdmins($applications);
        $campuses = $activeAdmins
            ->groupBy('kampus')
            ->map(function ($admins, $kampus) {
                $first = $admins->first();

                return [
                    'kampus' => $kampus,
                    'kode_kampus' => $first['kode_kampus'] ?? '-',
                    'admin_count' => $admins->count(),
                    'admin_name' => $first['nama'] ?? '-',
                    'email' => $first['email'] ?? '-',
                    'phone' => $first['phone'] ?? '-',
                    'laporan_masuk' => max(12, $admins->count() * 27),
                    'status' => 'Aktif',
                ];
            })
            ->values();

        return view('pages.superadmin-data-kampus', compact('campuses'));
    }

    private function superadminCandidates(AdminApplicationStore $applications)
    {
        $d = $this->dashboardData();
        $registeredCandidates = collect($applications->all())->map(fn ($item) => [
            'id' => $item['id'],
            'nama' => $item['nama'] ?? '-',
            'nidn' => $item['nidn'] ?? '-',
            'email' => $item['email'] ?? '-',
            'unit' => $item['unit'] ?? '-',
            'kampus' => $item['kampus'] ?? '-',
            'role' => $item['role'] ?? 'Calon Admin',
            'status' => $item['status'] ?? 'Menunggu',
            'alasan' => $item['alasan'] ?? '-',
            'surat_tugas_nama' => $item['surat_tugas_nama'] ?? null,
            'surat_tugas_path' => $item['surat_tugas_path'] ?? null,
        ]);

        return $registeredCandidates->concat($d['adminCandidates']);
    }

    private function superadminActiveAdmins(AdminApplicationStore $applications)
    {
        return collect($applications->approvedAdmins())->map(fn ($item) => [
            'id' => $item['id'],
            'nama' => $item['name'] ?? $item['nama'] ?? '-',
            'username' => $item['username'] ?? '-',
            'email' => $item['email'] ?? '-',
            'nidn' => $item['identifier'] ?? $item['nidn'] ?? '-',
            'kampus' => $item['kampus'] ?? '-',
            'kode_kampus' => $item['kode_kampus'] ?? '-',
            'unit' => $item['unit'] ?? '-',
            'phone' => $item['phone'] ?? '-',
            'status' => $item['status'] ?? '-',
            'created_at' => $item['created_at'] ?? '-',
        ]);
    }

    public function lihatDokumenAdmin($id, AdminApplicationStore $applications)
    {
        $document = $applications->document((string) $id);

        if (! $document || ! Storage::exists($document['path'])) {
            abort(404, 'Dokumen surat tugas tidak ditemukan.');
        }

        return response()->file(Storage::path($document['path']), [
            'Content-Type' => $document['mime'],
            'Content-Disposition' => 'inline; filename="'.$document['name'].'"',
        ]);
    }

    public function downloadDokumenAdmin($id, AdminApplicationStore $applications)
    {
        $document = $applications->document((string) $id);

        if (! $document || ! Storage::exists($document['path'])) {
            abort(404, 'Dokumen surat tugas tidak ditemukan.');
        }

        return Storage::download($document['path'], $document['name'], [
            'Content-Type' => $document['mime'],
        ]);
    }

    public function ubahStatusAdmin(Request $r, $id, AdminApplicationStore $applications)
    {
        $r->validate(['status' => 'required|in:Disetujui,Ditolak,Menunggu,Banned']);

        if ($applications->updateStatus((string) $id, $r->status)) {
            $message = match ($r->status) {
                'Disetujui' => "Pengajuan admin disetujui. Akun admin sudah dibuat dan bisa login.",
                'Ditolak' => "Pengajuan admin ditolak. Akun admin dinonaktifkan dan tidak tampil di data kampus aktif.",
                'Banned' => "Akun admin dibanned. Data admin aktif dihapus dan akun tidak bisa digunakan lagi.",
                default => "Status pengajuan admin diubah menjadi {$r->status}.",
            };

            return back()->with('success', $message);
        }

        return back()->with('success', "Status calon admin ID {$id} diubah menjadi {$r->status}.");
    }
}

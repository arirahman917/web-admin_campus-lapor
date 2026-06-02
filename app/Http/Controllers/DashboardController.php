<?php
namespace App\Http\Controllers;

use App\Services\CampusReportStore;

class DashboardController extends Controller
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

    public function index(CampusReportStore $reports)
    {
        $campusReports = collect($this->campusReports($reports));
        $activeBarangStatuses = ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Selesai', 'Barang Dihapus'];
        $fixedFasilitasStatuses = ['Sudah Diperbaiki', 'Selesai'];

        $stats = [
            'barangHilang' => $campusReports
                ->where('kategori', 'Barang Hilang')
                ->reject(fn ($item) => in_array($item['status'], $activeBarangStatuses, true))
                ->count(),
            'barangDitemukan' => $campusReports
                ->where('kategori', 'Barang Hilang')
                ->filter(fn ($item) => in_array($item['status'], ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Selesai'], true))
                ->count(),
            'fasilitasRusak' => $campusReports
                ->where('kategori', 'Fasilitas Rusak')
                ->reject(fn ($item) => in_array($item['status'], $fixedFasilitasStatuses, true))
                ->count(),
            'fasilitasDiperbaiki' => $campusReports
                ->where('kategori', 'Fasilitas Rusak')
                ->filter(fn ($item) => in_array($item['status'], $fixedFasilitasStatuses, true))
                ->count(),
        ];

        $laporanTerbaru = $campusReports
            ->sortByDesc('tanggal')
            ->take(5)
            ->map(fn ($item) => [
                'kategori' => $item['kategori'],
                'nama' => $item['kategori'] === 'Fasilitas Rusak' ? $item['namaFasilitas'] : $item['namaBarang'],
                'status' => $item['status'],
                'tanggal' => $item['tanggal'],
            ])
            ->values()
            ->all();

        return view('pages.dashboard', compact('stats', 'laporanTerbaru'));
    }

    public function trendsData(CampusReportStore $reports): \Illuminate\Http\JsonResponse
    {
        $campusReports = collect($this->campusReports($reports));

        $months = collect(range(5, 0))->map(fn ($offset) => now()->startOfMonth()->subMonths($offset));
        $chartData = $months->map(function ($month) use ($campusReports) {
            $reportsInMonth = $campusReports->filter(function ($item) use ($month) {
                try {
                    $date = \Carbon\Carbon::parse($item['tanggal']);
                } catch (\Throwable) {
                    return false;
                }

                return $date->isSameMonth($month);
            });

            return [
                'month' => $month->isoFormat('MMM'),
                'barangHilang' => $reportsInMonth->where('kategori', 'Barang Hilang')->count(),
                'fasilitasRusak' => $reportsInMonth->where('kategori', 'Fasilitas Rusak')->count(),
            ];
        })->values()->all();

        return response()->json($chartData);
    }

    private function campusReports(CampusReportStore $reports): array
    {
        return collect($reports->forCampus($this->campusKey()))->map(fn ($item) => [
            'id' => $item['id'],
            'namaBarang' => $item['title'] ?? '-',
            'namaFasilitas' => $item['title'] ?? '-',
            'pelapor' => $item['reporter_name'] ?? '-',
            'pelapor_nim' => $item['reporter_id'] ?? null,
            'lokasi' => $item['location'] ?? '-',
            'tanggal' => $item['created_at'] ?? now()->toIso8601String(),
            'status' => $item['status'] ?? 'Aktif',
            'deskripsi' => $item['description'] ?? '-',
            'kategori' => $item['category'] ?? '-',
            'foto' => $item['photo_data'] ?? null,
        ])->all();
    }
}

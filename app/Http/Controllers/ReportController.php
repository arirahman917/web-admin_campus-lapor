<?php
namespace App\Http\Controllers;

use App\Exports\ReportsExport;
use App\Services\CampusReportStore;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
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

    public function barangHilang(CampusReportStore $reports)
    {
        $d = $this->dashboardData();
        $campusReports = collect($this->campusReports($reports));
        $barangHilang = collect($d['barangHilang'])
            ->concat($campusReports
                ->where('kategori', 'Barang Hilang')
                ->reject(fn ($item) => in_array($item['status'], ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Selesai', 'Barang Dihapus'], true))
                ->values())
            ->values();
        $barangDitemukan = collect($d['barangDitemukan'])
            ->concat($campusReports
                ->where('kategori', 'Barang Hilang')
                ->filter(fn ($item) => in_array($item['status'], ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Selesai'], true))
                ->map(fn ($item) => array_merge($item, ['status' => $item['status'] === 'Ditemukan' ? 'Menunggu Diambil' : $item['status']]))
                ->values())
            ->values();

        return view('pages.barang-hilang', ['barangHilang'=>$barangHilang,'barangDitemukan'=>$barangDitemukan]);
    }

    public function ubahStatusBarang(Request $r, $id, CampusReportStore $reports)
    {
        $report = \App\Models\CampusReport::find($id);

        if ($report && $reports->updateStatus($this->campusKey(), (string) $id, 'Menunggu Diambil')) {
            // 1. Kirim Chat Otomatis
            $chatStore = app(\App\Services\ChatStore::class);
            $chatStore->send([
                'sender_id' => session('auth_username', 'admin1'),
                'sender_name' => session('auth_name', 'Admin Kampus'),
                'sender_role' => 'admin',
                'admin_username' => session('auth_username', 'admin1'),
                'campus_key' => $this->campusKey(),
                'receiver_id' => $report->reporter_id,
                'receiver_identifier' => $report->reporter_id,
                'receiver_name' => $report->reporter_name,
                'body' => "Halo {$report->reporter_name}, barang Anda '" . $report->title . "' yang dilaporkan hilang di lokasi '" . $report->location . "' telah ditemukan! Silakan datang ke kantor pelayanan kampus untuk mengambilnya.",
            ]);

            // 2. Buat Notifikasi Otomatis
            \App\Models\Notification::create([
                'user_id' => $report->reporter_id,
                'title' => 'Barang Hilang Ditemukan!',
                'body' => "Barang '" . $report->title . "' Anda telah ditemukan. Silakan hubungi admin.",
                'is_read' => false,
                'created_at' => now()->toIso8601String(),
            ]);

            return back()->with('success', "Barang hilang ditandai ditemukan. Notifikasi & chat otomatis telah dikirim ke pelapor.");
        }

        return back()->with('error', "Status barang ID {$id} gagal diperbarui.");
    }

    public function hapusBarangDitemukan($id, CampusReportStore $reports)
    {
        if ($reports->updateStatus($this->campusKey(), (string) $id, 'Barang Dihapus')) {
            return back()->with('success', "Laporan barang ditemukan dihapus dari daftar admin.");
        }

        return back()->with('error', "Laporan barang ditemukan ID {$id} gagal dihapus dari daftar.");
    }

    public function tandaiBarangDiambil($id, CampusReportStore $reports)
    {
        if ($reports->updateStatus($this->campusKey(), (string) $id, 'Sudah Diambil')) {
            return back()->with('success', "Barang ditandai sudah diambil oleh pelapor.");
        }

        return back()->with('error', "Status barang ID {$id} gagal diperbarui menjadi sudah diambil.");
    }

    public function fasilitasRusak(CampusReportStore $reports)
    {
        $d = $this->dashboardData();
        $campusReports = collect($this->campusReports($reports));
        $fasilitasRusak = collect($d['fasilitasRusak'])
            ->concat($campusReports
                ->where('kategori', 'Fasilitas Rusak')
                ->reject(fn ($item) => in_array($item['status'], ['Sudah Diperbaiki', 'Selesai'], true))
                ->values())
            ->values();
        $fasilitasDiperbaiki = collect($d['fasilitasDiperbaiki'])
            ->concat($campusReports
                ->where('kategori', 'Fasilitas Rusak')
                ->filter(fn ($item) => in_array($item['status'], ['Sudah Diperbaiki', 'Selesai'], true))
                ->values())
            ->values();

        return view('pages.fasilitas-rusak', ['fasilitasRusak'=>$fasilitasRusak,'fasilitasDiperbaiki'=>$fasilitasDiperbaiki]);
    }

    public function tandaiDiperbaiki(Request $r, $id, CampusReportStore $reports)
    {
        if ($reports->updateStatus($this->campusKey(), (string) $id, 'Sudah Diperbaiki')) {
            return back()->with('success', "Fasilitas ditandai sudah diperbaiki dan notifikasi dikirim ke civitas pelapor.");
        }

        return back()->with('success', "Fasilitas ID {$id} ditandai sudah diperbaiki. Notifikasi dikirim ke civitas pelapor.");
    }

    public function hapusFasilitasDiperbaiki($id, CampusReportStore $reports)
    {
        if ($reports->updateStatus($this->campusKey(), (string) $id, 'Dilaporkan')) {
            return back()->with('success', "Laporan fasilitas dikembalikan ke daftar fasilitas rusak.");
        }

        return back()->with('success', "Laporan fasilitas diperbaiki ID {$id} berhasil dihapus dari daftar.");
    }

    public function exportBarang(string $format, CampusReportStore $reports)
    {
        $rows = collect($this->approvedBarangRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i, 'Barang Hilang', 'namaBarang'))
            ->all();

        return $this->downloadReport($format, 'laporan-barang-ditemukan', 'Laporan Barang Ditemukan', $rows);
    }

    public function exportBarangHilang(string $format, CampusReportStore $reports)
    {
        $rows = collect($this->pendingBarangRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i, 'Barang Hilang', 'namaBarang'))
            ->all();

        return $this->downloadReport($format, 'laporan-barang-hilang', 'Laporan Barang Hilang', $rows);
    }

    public function exportBarangSemua(string $format, CampusReportStore $reports)
    {
        $hilang = collect($this->pendingBarangRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i, 'Barang Hilang', 'namaBarang'))
            ->all();
        $ditemukan = collect($this->approvedBarangRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i + count($hilang), 'Barang Hilang', 'namaBarang'))
            ->all();
        $rows = array_merge($hilang, $ditemukan);

        return $this->downloadReport($format, 'laporan-semua-barang', 'Laporan Semua Barang (Hilang & Ditemukan)', $rows);
    }

    public function exportFasilitas(string $format, CampusReportStore $reports)
    {
        $rows = collect($this->approvedFasilitasRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i, 'Fasilitas Rusak', 'namaFasilitas'))
            ->all();

        return $this->downloadReport($format, 'laporan-fasilitas-diperbaiki', 'Laporan Fasilitas Sudah Diperbaiki', $rows);
    }

    public function exportFasilitasRusak(string $format, CampusReportStore $reports)
    {
        $rows = collect($this->pendingFasilitasRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i, 'Fasilitas Rusak', 'namaFasilitas'))
            ->all();

        return $this->downloadReport($format, 'laporan-fasilitas-rusak', 'Laporan Fasilitas Rusak', $rows);
    }

    public function exportFasilitasSemua(string $format, CampusReportStore $reports)
    {
        $rusak = collect($this->pendingFasilitasRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i, 'Fasilitas Rusak', 'namaFasilitas'))
            ->all();
        $diperbaiki = collect($this->approvedFasilitasRows($reports))
            ->values()
            ->map(fn ($item, $i) => $this->exportRow($item, $i + count($rusak), 'Fasilitas Rusak', 'namaFasilitas'))
            ->all();
        $rows = array_merge($rusak, $diperbaiki);

        return $this->downloadReport($format, 'laporan-semua-fasilitas', 'Laporan Semua Fasilitas (Rusak & Diperbaiki)', $rows);
    }

    private function pendingBarangRows(CampusReportStore $reports): array
    {
        $d = $this->dashboardData();
        $campusReports = collect($this->campusReports($reports));

        return collect($d['barangHilang'])
            ->concat($campusReports
                ->where('kategori', 'Barang Hilang')
                ->reject(fn ($item) => in_array($item['status'], ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Selesai', 'Barang Dihapus'], true))
                ->values())
            ->values()
            ->all();
    }

    private function approvedBarangRows(CampusReportStore $reports): array
    {
        $d = $this->dashboardData();
        $campusReports = collect($this->campusReports($reports));

        return collect($d['barangDitemukan'])
            ->concat($campusReports
                ->where('kategori', 'Barang Hilang')
                ->filter(fn ($item) => in_array($item['status'], ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Selesai'], true))
                ->values())
            ->values()
            ->all();
    }

    private function pendingFasilitasRows(CampusReportStore $reports): array
    {
        $d = $this->dashboardData();
        $campusReports = collect($this->campusReports($reports));

        return collect($d['fasilitasRusak'])
            ->concat($campusReports
                ->where('kategori', 'Fasilitas Rusak')
                ->reject(fn ($item) => in_array($item['status'], ['Sudah Diperbaiki', 'Selesai'], true))
                ->values())
            ->values()
            ->all();
    }

    private function approvedFasilitasRows(CampusReportStore $reports): array
    {
        $d = $this->dashboardData();
        $campusReports = collect($this->campusReports($reports));

        return collect($d['fasilitasDiperbaiki'])
            ->concat($campusReports
                ->where('kategori', 'Fasilitas Rusak')
                ->filter(fn ($item) => in_array($item['status'], ['Sudah Diperbaiki', 'Selesai'], true))
                ->values())
            ->values()
            ->all();
    }

    private function exportRow(array $item, int $index, string $jenis, string $titleKey): array
    {
        return [
            'id' => $item['id'] ?? ($index + 1),
            'nama_pelapor' => $item['pelapor'] ?? '-',
            'role' => $item['role'] ?? 'Civitas',
            'jenis_laporan' => $jenis,
            'judul' => $item[$titleKey] ?? $item['judul'] ?? '-',
            'lokasi' => $item['lokasi'] ?? '-',
            'status' => $item['status'] ?? '-',
            'tanggal' => \Carbon\Carbon::parse($item['tanggal'] ?? now())->isoFormat('D MMM YYYY'),
            'deskripsi' => $item['deskripsi'] ?? '',
            'foto' => $item['foto'] ?? null,
        ];
    }

    private function reportStats(array $rows): array
    {
        $finishedStatuses = ['Ditemukan', 'Menunggu Diambil', 'Sudah Diambil', 'Sudah Diperbaiki', 'Selesai', 'Ditutup'];

        return [
            'total_laporan' => count($rows),
            'total_barang_hilang' => collect($rows)->where('jenis_laporan', 'Barang Hilang')->count(),
            'total_fasilitas_rusak' => collect($rows)->where('jenis_laporan', 'Fasilitas Rusak')->count(),
            'total_selesai' => collect($rows)->whereIn('status', $finishedStatuses)->count(),
            'total_pending' => collect($rows)->reject(fn ($row) => in_array($row['status'], $finishedStatuses, true))->count(),
            'printed_at' => now()->format('d M Y H:i'),
        ];
    }

    private function downloadReport(string $format, string $filename, string $title, array $rows)
    {
        $stats = $this->reportStats($rows);

        return match ($format) {
            'excel' => Excel::download(new ReportsExport($title, $rows, $stats), $filename.'.xlsx'),
            'pdf' => Pdf::loadView('exports.report-pdf', [
                'title' => $title,
                'rows' => $rows,
                'stats' => $stats,
                'adminName' => session('auth_name', 'Admin Kampus'),
                'printedAt' => $stats['printed_at'],
            ])->setPaper('a4', 'landscape')->download($filename.'.pdf'),
            default => abort(404),
        };
    }
}

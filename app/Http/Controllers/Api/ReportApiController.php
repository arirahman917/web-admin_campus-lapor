<?php

namespace App\Http\Controllers\Api;

use App\Services\CampusReportStore;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class ReportApiController extends BaseApiController
{
    public function storeLaporanBarang(Request $request, CampusReportStore $reports)
    {
        return $this->storeReport($request, $reports, 'Barang Hilang');
    }

    public function storeLaporanFasilitas(Request $request, CampusReportStore $reports)
    {
        return $this->storeReport($request, $reports, 'Fasilitas Rusak');
    }

    public function laporanBarang(Request $request, CampusReportStore $reports)
    {
        return $this->reportList($request, $reports, 'Barang Hilang');
    }

    public function laporanFasilitas(Request $request, CampusReportStore $reports)
    {
        return $this->reportList($request, $reports, 'Fasilitas Rusak');
    }

    public function laporanByReporter(string $reporterId, CampusReportStore $reports)
    {
        return response()->json(['reports' => $reports->forReporter($reporterId)]);
    }

    public function laporanDetail(string $id)
    {
        $report = $this->reportById($id);

        return $report ? response()->json(['data' => $report]) : response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
    }

    public function laporanStatus(string $id, Request $request)
    {
        $data = $request->validate(['status' => 'required|string']);
        $updated = $this->updateReport($id, ['status' => $data['status']]);

        return response()->json(['success' => $updated]);
    }

    public function laporanUpdate(string $id, Request $request)
    {
        $data = $request->only(['title', 'location', 'tag', 'description', 'photo_data']);
        $updated = $this->updateReport($id, array_filter($data, fn ($value) => $value !== null));

        return response()->json(['success' => $updated]);
    }

    private function storeReport(Request $request, CampusReportStore $reports, string $category)
    {
        $data = $request->validate([
            'reporter_id' => 'required|string',
            'reporter_name' => 'required|string',
            'title' => 'required|string|max:160',
            'location' => 'required|string|max:160',
            'tag' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:1000',
            'photo_data' => 'nullable|string',
        ]);
        $data['category'] = $category;

        return response()->json(['report' => $reports->createFromMobile($data)], 201);
    }

    private function reportList(Request $request, CampusReportStore $reports, string $category)
    {
        $items = collect($reports->forCampus($request->query('campus_key', $request->query('kode_kampus', ''))))
            ->where('category', $category)
            ->values();

        return response()->json(['data' => $items]);
    }

    private function reportById(string $id): ?array
    {
        if (! preg_match('/^[a-f\d]{24}$/i', $id)) {
            return null;
        }

        $report = $this->db()->selectCollection('campus_reports')->findOne(['_id' => new ObjectId($id)]);
        if (! $report) {
            return null;
        }

        $array = json_decode(json_encode($report), true);
        $array['id'] = $array['_id']['$oid'] ?? $id;

        // Dapatkan foto profil terkini milik pelapor
        $reporterId = $array['reporter_id'] ?? null;
        if ($reporterId) {
            $user = $this->db()->selectCollection('users')->findOne([
                '$or' => [
                    ['username' => $reporterId],
                    ['nim' => $reporterId],
                    ['identifier' => $reporterId],
                ],
                'role' => 'civitas',
                'status' => ['$in' => ['Aktif', 'aktif']],
            ]);
            $array['reporter_photo'] = $user['profile_photo'] ?? null;
        } else {
            $array['reporter_photo'] = null;
        }

        return $array;
    }

    private function updateReport(string $id, array $data): bool
    {
        if (! preg_match('/^[a-f\d]{24}$/i', $id)) {
            return false;
        }

        $data['updated_at'] = now()->toIso8601String();
        $result = $this->db()->selectCollection('campus_reports')->updateOne(['_id' => new ObjectId($id)], ['$set' => $data]);

        return $result->getMatchedCount() > 0;
    }
}

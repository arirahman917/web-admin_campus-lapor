<?php

namespace App\Http\Controllers\Api;

use App\Services\AdminApplicationStore;
use App\Services\CampusDataStore;
use Illuminate\Http\Request;

class CampusApiController extends BaseApiController
{
    public function kampusList(AdminApplicationStore $applications)
    {
        return response()->json(['data' => $applications->all()]);
    }

    public function kampusStatus(string $id, Request $request, AdminApplicationStore $applications)
    {
        $data = $request->validate(['status' => 'required|in:Disetujui,Ditolak,Menunggu,Banned']);

        return response()->json(['success' => $applications->updateStatus($id, $data['status'])]);
    }

    public function civitasList(Request $request, CampusDataStore $campusData)
    {
        return response()->json(['data' => $campusData->civitasUsers($request->query('campus_key', $request->query('kode_kampus', '')))]);
    }

    public function civitasStatus(string $id, Request $request, CampusDataStore $campusData)
    {
        $data = $request->validate(['status' => 'required|in:Aktif,Banned,Menunggu', 'campus_key' => 'required|string']);

        return response()->json(['success' => $campusData->setCivitasStatus($data['campus_key'], $id, $data['status'])]);
    }

    public function approvedKampus()
    {
        $admins = $this->users()->find(['role' => 'admin', 'status' => 'aktif'], ['projection' => ['password' => 0]])->toArray();

        return response()->json(['data' => array_map(fn ($item) => $this->publicUser($item), $admins)]);
    }
}

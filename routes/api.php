<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CampusApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\ReportApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login/super-admin', [AuthApiController::class, 'loginSuperAdmin']);
Route::post('/auth/login/admin-kampus', [AuthApiController::class, 'loginAdminKampus']);
Route::post('/auth/login/civitas', [AuthApiController::class, 'loginCivitas']);
Route::post('/kampus/register', [AuthApiController::class, 'registerKampus']);
Route::post('/civitas/register', [AuthApiController::class, 'registerCivitas']);
Route::get('/kampus/approved', [CampusApiController::class, 'approvedKampus']);

Route::middleware('auth.api')->group(function () {
    Route::post('/profile/update', [AuthApiController::class, 'updateProfile']);
    Route::get('/super-admin/kampus', [CampusApiController::class, 'kampusList']);
    Route::patch('/super-admin/kampus/{id}/status', [CampusApiController::class, 'kampusStatus']);

    Route::get('/admin-kampus/civitas', [CampusApiController::class, 'civitasList']);
    Route::patch('/admin-kampus/civitas/{id}/status', [CampusApiController::class, 'civitasStatus']);

    Route::post('/laporan-barang', [ReportApiController::class, 'storeLaporanBarang']);
    Route::get('/laporan-barang', [ReportApiController::class, 'laporanBarang']);
    Route::get('/laporan-barang/{id}', [ReportApiController::class, 'laporanDetail']);
    Route::patch('/laporan-barang/{id}/status', [ReportApiController::class, 'laporanStatus']);
    Route::patch('/laporan-barang/{id}', [ReportApiController::class, 'laporanUpdate']);

    Route::post('/laporan-fasilitas', [ReportApiController::class, 'storeLaporanFasilitas']);
    Route::get('/laporan-fasilitas', [ReportApiController::class, 'laporanFasilitas']);
    Route::get('/laporan-fasilitas/{id}', [ReportApiController::class, 'laporanDetail']);
    Route::patch('/laporan-fasilitas/{id}/status', [ReportApiController::class, 'laporanStatus']);
    Route::get('/mobile/reports/{reporterId}', [ReportApiController::class, 'laporanByReporter']);

    Route::post('/chats', [ChatApiController::class, 'storeChat']);
    Route::get('/chats/{participantId}', [ChatApiController::class, 'chatThread']);
    Route::get('/chats/civitas/{nim}', [ChatApiController::class, 'civitasThreads']);

    Route::get('/notifikasi', [NotificationApiController::class, 'notifications']);
    Route::patch('/notifikasi/{id}/read', [NotificationApiController::class, 'notificationRead']);
});

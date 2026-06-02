<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SuperAdminController;

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::get('/daftar-admin', [AuthController::class, 'showAdminRegistration'])->name('admin-register');
Route::post('/daftar-admin', [AuthController::class, 'storeAdminRegistration'])->name('admin-register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['role:admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/trends-data', [DashboardController::class, 'trendsData'])->name('dashboard.trends-data');
    
    // Rute Chat Web Aman (Hanya Admin)
    Route::post('/chat/send', [ChatController::class, 'chatSend'])->name('chat.send');
    Route::get('/chat/thread/{participantId}', [ChatController::class, 'chatThread'])->name('chat.thread');
    Route::post('/chat/thread/{participantId}/read', [ChatController::class, 'chatMarkRead'])->name('chat.read');
    
    // Manajemen Laporan & Barang
    Route::get('/barang-hilang', [ReportController::class, 'barangHilang'])->name('barang-hilang');
    Route::get('/barang-hilang/export/{format}', [ReportController::class, 'exportBarang'])->name('barang-hilang.export');
    Route::get('/barang-hilang/export-hilang/{format}', [ReportController::class, 'exportBarangHilang'])->name('barang-hilang.export-hilang');
    Route::get('/barang-hilang/export-semua/{format}', [ReportController::class, 'exportBarangSemua'])->name('barang-hilang.export-semua');
    Route::patch('/barang-hilang/{id}/ubah-status', [ReportController::class, 'ubahStatusBarang'])->name('barang-hilang.ubah-status');
    Route::patch('/barang-ditemukan/{id}/diambil', [ReportController::class, 'tandaiBarangDiambil'])->name('barang-ditemukan.diambil');
    Route::delete('/barang-ditemukan/{id}', [ReportController::class, 'hapusBarangDitemukan'])->name('barang-ditemukan.hapus');
    Route::get('/fasilitas-rusak', [ReportController::class, 'fasilitasRusak'])->name('fasilitas-rusak');
    Route::get('/fasilitas-rusak/export/{format}', [ReportController::class, 'exportFasilitas'])->name('fasilitas-rusak.export');
    Route::get('/fasilitas-rusak/export-rusak/{format}', [ReportController::class, 'exportFasilitasRusak'])->name('fasilitas-rusak.export-rusak');
    Route::get('/fasilitas-rusak/export-semua/{format}', [ReportController::class, 'exportFasilitasSemua'])->name('fasilitas-rusak.export-semua');
    Route::patch('/fasilitas-rusak/{id}/tandai', [ReportController::class, 'tandaiDiperbaiki'])->name('fasilitas-rusak.tandai');
    Route::delete('/fasilitas-diperbaiki/{id}', [ReportController::class, 'hapusFasilitasDiperbaiki'])->name('fasilitas-diperbaiki.hapus');
    
    // Sistem Pesan/Chat Admin
    Route::get('/pesan', [ChatController::class, 'pesan'])->name('pesan');
    Route::post('/pesan/kirim', [ChatController::class, 'kirimPesan'])->name('pesan.kirim');
    
    // Manajemen Kampus & Lokasi
    Route::get('/manajemen-kampus', [LocationController::class, 'manajemenKampus'])->name('manajemen-kampus');
    Route::patch('/manajemen-kampus/user/{id}/toggle', [LocationController::class, 'toggleUserStatus'])->name('manajemen-kampus.toggle-status');
    Route::post('/manajemen-kampus/lokasi', [LocationController::class, 'simpanLokasi'])->name('manajemen-kampus.lokasi.simpan');
    Route::delete('/manajemen-kampus/lokasi/{id}', [LocationController::class, 'hapusLokasi'])->name('manajemen-kampus.lokasi.hapus');
});

Route::middleware(['role:superadmin'])->group(function () {
    Route::get('/superadmin/seleksi-admin', [SuperAdminController::class, 'seleksiAdmin'])->name('superadmin.seleksi-admin');
    Route::get('/superadmin/admin-aktif', [SuperAdminController::class, 'adminAktif'])->name('superadmin.admin-aktif');
    Route::get('/superadmin/data-kampus', [SuperAdminController::class, 'dataKampus'])->name('superadmin.data-kampus');
    Route::get('/superadmin/seleksi-admin/{id}/dokumen', [SuperAdminController::class, 'lihatDokumenAdmin'])->name('superadmin.seleksi-admin.dokumen');
    Route::get('/superadmin/seleksi-admin/{id}/dokumen/download', [SuperAdminController::class, 'downloadDokumenAdmin'])->name('superadmin.seleksi-admin.dokumen.download');
    Route::patch('/superadmin/seleksi-admin/{id}', [SuperAdminController::class, 'ubahStatusAdmin'])->name('superadmin.seleksi-admin.ubah-status');
});

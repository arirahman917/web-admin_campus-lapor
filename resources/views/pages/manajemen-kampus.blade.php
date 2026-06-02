@extends('layouts.app')
@php $title = 'Manajemen Kampus & User'; @endphp

@section('content')
<div class="tabs-header">
  <button class="tab-btn active" data-tab-group="kampus" data-tab="profil" onclick="switchTab('kampus','profil')">Profil Kampus</button>
  <button class="tab-btn" data-tab-group="kampus" data-tab="lokasi" onclick="switchTab('kampus','lokasi')">Daftar Lokasi</button>
  <button class="tab-btn" data-tab-group="kampus" data-tab="users" onclick="switchTab('kampus','users')">Data User</button>
</div>

<div class="tab-panel active" data-panel-group="kampus" data-panel="profil">
  <div class="card" style="max-width:720px;">
    <div class="profile-banner">
      <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" opacity=".3"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    </div>
    <div class="card-body">
      <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:.25rem;">{{ $campusProfile['name'] }}</h2>
      <p style="color:#64748b;font-size:.875rem;margin-bottom:1.5rem;">{{ $campusProfile['code'] }} &bull; Admin Portal &amp; Laporan Civitas Kampus</p>
      <div class="info-grid">
        <div class="info-item">
          <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
          <div><p class="info-label">Alamat Utama</p><p class="info-value">{{ $campusProfile['address'] }}</p></div>
        </div>
        <div class="info-item">
          <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
          <div><p class="info-label">Email Administrator</p><p class="info-value">{{ $campusProfile['email'] }}</p></div>
        </div>
        <div class="info-item">
          <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.8 19.79 19.79 0 0 1 1.63 5.17 2 2 0 0 1 3.6 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
          <div><p class="info-label">Kontak Darurat</p><p class="info-value">{{ $campusProfile['emergency'] }}</p></div>
        </div>
        <div class="info-item">
          <div class="info-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
          <div><p class="info-label">Total Civitas Terdaftar</p><p class="info-value" style="font-size:1.5rem;font-weight:700;color:#7c3aed;">{{ $users->where('status', 'Aktif')->count() }} <span style="font-size:.8rem;font-weight:400;color:#64748b;">akun aktif</span></p></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="tab-panel" data-panel-group="kampus" data-panel="lokasi">
  <div class="dashboard-grid">
    <div class="card">
      <div class="card-header">
        <h3>Daftar Lokasi</h3>
        <p>Lokasi ini akan menjadi pilihan dropdown di form pelaporan civitas.</p>
      </div>
      <div class="card-body" style="padding:0 0 1rem;">
        <div class="table-wrap" style="border-left:none;border-right:none;border-radius:0;">
          <table>
            <thead><tr><th>No</th><th>Daftar Lokasi</th><th>Area</th><th style="text-align:right;">Aksi</th></tr></thead>
            <tbody>
              @forelse($locations as $i => $location)
              <tr>
                <td>{{ $i + 1 }}</td>
                <td style="font-weight:600;">{{ $location['nama'] }}</td>
                <td>{{ $location['area'] }}</td>
                <td style="text-align:right;">
                  <form method="POST" action="{{ route('manajemen-kampus.lokasi.hapus', $location['id']) }}" style="display:inline;" onsubmit="return confirm('Apakah lokasi {{ $location['nama'] }} mau dihapus?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-icon-sm btn-icon-red" title="Hapus lokasi">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                  </form>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="4" style="text-align:center;color:#64748b;padding:1.5rem;">Belum ada lokasi untuk kampus ini.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Tambah Lokasi</h3>
        <p>Admin bisa menambah lokasi agar civitas tinggal memilih saat membuat laporan.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('manajemen-kampus.lokasi.simpan') }}">
          @csrf
          <div class="form-group">
            <label for="nama">Nama Lokasi</label>
            <input type="text" id="nama" name="nama" placeholder="Contoh: Lab Komputer Lt. 2">
          </div>
          <div class="form-group">
            <label for="area">Area / Kategori</label>
            <input type="text" id="area" name="area" placeholder="Contoh: Akademik">
          </div>
          <button class="btn btn-primary" type="submit">Simpan Lokasi</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="tab-panel" data-panel-group="kampus" data-panel="users">
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div><h3>Data Pengguna Aplikasi</h3><p>Kelola akses civitas akademik kampus.</p></div>
      <div class="search-input-wrap" style="width:280px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" id="searchUser" placeholder="Cari nama, NIM, email..." oninput="filterTable('searchUser','tableUsers')" />
      </div>
    </div>
    <div class="card-body" style="padding:0 0 1.5rem;">
      <div class="table-wrap" style="border-left:none;border-right:none;border-radius:0;">
        <table id="tableUsers">
          <thead><tr><th>No</th><th>Nama</th><th>NIM/NIDN</th><th>Email</th><th>Role</th><th>Status</th><th style="text-align:right;">Aksi</th></tr></thead>
          <tbody>
            @forelse($users as $i => $user)
            <tr>
              <td>{{ $i+1 }}</td>
              <td style="font-weight:500;">{{ $user['nama'] }}</td>
              <td>{{ $user['nim'] }}</td>
              <td style="color:#64748b;">{{ $user['email'] }}</td>
              <td><span class="badge badge-{{ strtolower($user['role']) }}">{{ $user['role'] }}</span></td>
              <td><span class="badge badge-{{ $user['status']==='Aktif' || $user['status']==='aktif' ? 'user-aktif' : ($user['status']==='Menunggu' ? 'menunggu' : 'banned') }}">{{ $user['status'] }}</span></td>
              <td style="text-align:right;">
                <button class="btn-icon-sm btn-icon-blue" onclick='showProfil({{ json_encode($user) }})' title="Lihat Profil">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <a href="{{ route('pesan') }}" class="btn-icon-sm btn-icon-green" title="Kirim Pesan">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </a>
                <form method="POST" action="{{ route('manajemen-kampus.toggle-status', $user['id']) }}" style="display:inline;">
                  @csrf @method('PATCH')
                  @if($user['status']==='Menunggu')
                  <input type="hidden" name="status" value="Aktif">
                  <button type="submit" class="btn-icon-sm btn-icon-green" title="Setujui civitas">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                  @elseif($user['status']==='Aktif' || $user['status']==='aktif')
                  <input type="hidden" name="status" value="Banned">
                  <button type="submit" class="btn-icon-sm btn-icon-red" title="Banned User">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                  </button>
                  @else
                  <input type="hidden" name="status" value="Aktif">
                  <button type="submit" class="btn-icon-sm btn-icon-green" title="Unban User">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  </button>
                  @endif
                </form>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" style="text-align:center;color:#64748b;padding:1.5rem;">Belum ada civitas terdaftar untuk kampus ini.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($bannedUsers->count() > 0)
      <div style="padding:0 1.5rem;margin-top:1.5rem;">
        <h3 style="color:#ef4444;display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          Daftar User Banned
        </h3>
        <div class="banned-grid">
          @foreach($bannedUsers as $user)
          <div class="banned-card">
            <div><p class="banned-name">{{ $user['nama'] }}</p><p class="banned-nim">{{ $user['nim'] }}</p></div>
            <form method="POST" action="{{ route('manajemen-kampus.toggle-status', $user['id']) }}">
              @csrf @method('PATCH')
              <input type="hidden" name="status" value="Aktif">
              <button type="submit" class="btn btn-danger btn-sm">Unban</button>
            </form>
          </div>
          @endforeach
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
function filterTable(inputId, tableId) {
  const q = document.getElementById(inputId).value.toLowerCase();
  document.getElementById(tableId).querySelectorAll('tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
@endpush


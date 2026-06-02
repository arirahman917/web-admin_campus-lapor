@extends('layouts.app')
@php $title = 'Superadmin - Seleksi Admin'; @endphp

@section('content')
<div class="page-top">
  <div>
    <h2>Seleksi Daftar Admin</h2>
    <p class="page-desc">Superadmin meninjau pengajuan admin unit sebelum akses pengelolaan laporan diberikan.</p>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Menunggu Review</span><span class="stat-icon" style="background:#fef3c7;color:#d97706;">{{ $summary['menunggu'] }}</span></div>
    <div class="stat-sub">Pengajuan perlu keputusan</div>
  </div>
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Disetujui</span><span class="stat-icon" style="background:#ede9fe;color:#7c3aed;">{{ $summary['disetujui'] }}</span></div>
    <div class="stat-sub">Admin aktif untuk unit kampus</div>
  </div>
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Ditolak</span><span class="stat-icon" style="background:#fee2e2;color:#ef4444;">{{ $summary['ditolak'] }}</span></div>
    <div class="stat-sub">Pengajuan perlu perbaikan data</div>
  </div>
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Banned</span><span class="stat-icon" style="background:#fee2e2;color:#b91c1c;">{{ $summary['banned'] }}</span></div>
    <div class="stat-sub">Akun tidak bisa digunakan lagi</div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <div>
      <h3>Daftar Calon Admin</h3>
      <p>Validasi unit, email kampus, dan kebutuhan akses.</p>
    </div>
    <div class="search-input-wrap" style="width:280px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" id="searchAdmin" placeholder="Cari calon admin..." oninput="filterTable('searchAdmin','tableAdmin')" />
    </div>
  </div>
  <div class="card-body" style="padding:0 0 1.5rem;">
    <div class="table-wrap" style="border-left:none;border-right:none;border-radius:0;">
      <table id="tableAdmin">
        <thead>
          <tr><th>Nama</th><th>NIDN/NIP</th><th>Kampus</th><th>Unit</th><th>Email</th><th>Alasan</th><th>Dokumen</th><th>Status</th><th style="text-align:right;">Aksi</th></tr>
        </thead>
        <tbody>
          @foreach($candidates as $candidate)
          <tr>
            <td style="font-weight:600;">{{ $candidate['nama'] }}</td>
            <td>{{ $candidate['nidn'] }}</td>
            <td>{{ $candidate['kampus'] ?? '-' }}</td>
            <td>{{ $candidate['unit'] }}</td>
            <td style="color:#64748b;">{{ $candidate['email'] }}</td>
            <td style="max-width:280px;">{{ $candidate['alasan'] }}</td>
            <td>
              @if(!empty($candidate['surat_tugas_path']))
                <a class="btn btn-outline" href="{{ route('superadmin.seleksi-admin.dokumen', $candidate['id']) }}" target="_blank" rel="noopener">
                  Lihat Dokumen
                </a>
                <a class="btn btn-outline" href="{{ route('superadmin.seleksi-admin.dokumen.download', $candidate['id']) }}">
                  Download
                </a>
                <span class="table-muted">{{ $candidate['surat_tugas_nama'] ?? 'Surat tugas' }}</span>
              @else
                <span class="table-muted">Belum ada</span>
              @endif
            </td>
            <td><span class="badge badge-{{ strtolower($candidate['status']) }}">{{ $candidate['status'] }}</span></td>
            <td style="text-align:right;white-space:nowrap;">
              @if(($candidate['status'] ?? '') === 'Banned')
                <span class="table-muted">History banned</span>
              @else
              <form method="POST" action="{{ route('superadmin.seleksi-admin.ubah-status', $candidate['id']) }}" style="display:inline;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="Disetujui">
                <button class="btn-icon-sm btn-icon-green" title="Setujui admin" type="submit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </form>
              <form method="POST" action="{{ route('superadmin.seleksi-admin.ubah-status', $candidate['id']) }}" style="display:inline;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="Ditolak">
                <button class="btn-icon-sm btn-icon-red" title="Tolak pengajuan" type="submit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </form>
              <form method="POST" action="{{ route('superadmin.seleksi-admin.ubah-status', $candidate['id']) }}" style="display:inline;" onsubmit="return confirm('Banned akun admin {{ $candidate['nama'] }}? Akun tidak bisa digunakan dan data admin aktif akan dihapus.')">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="Banned">
                <button class="btn-icon-sm btn-icon-red" title="Banned admin" type="submit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                </button>
              </form>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
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


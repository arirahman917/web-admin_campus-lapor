@extends('layouts.app')
@php $title = 'Superadmin - Admin Aktif'; @endphp

@section('content')
<div class="page-top">
  <div>
    <h2>Admin Aktif</h2>
    <p class="page-desc">Daftar akun admin yang sudah disetujui dan mengelola kampus masing-masing.</p>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Total Admin</span><span class="stat-icon" style="background:#ede9fe;color:#7c3aed;">{{ $summary['total'] }}</span></div>
    <div class="stat-sub">Akun admin terdaftar</div>
  </div>
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Kampus Terhubung</span><span class="stat-icon" style="background:#dbeafe;color:#3b82f6;">{{ $summary['kampus'] }}</span></div>
    <div class="stat-sub">Data tiap kampus terpisah</div>
  </div>
  <div class="stat-card">
    <div class="stat-top"><span class="stat-label">Status Aktif</span><span class="stat-icon" style="background:#dcfce7;color:#16a34a;">{{ $summary['aktif'] }}</span></div>
    <div class="stat-sub">Bisa login dan mengelola laporan</div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <div>
      <h3>Manajemen Admin Aktif</h3>
      <p>Pantau admin aktif, kampus, dan kontak penanggung jawab.</p>
    </div>
    <div class="search-input-wrap" style="width:280px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" id="searchActiveAdmin" placeholder="Cari admin aktif..." oninput="filterTable('searchActiveAdmin','tableActiveAdmin')" />
    </div>
  </div>
  <div class="card-body" style="padding:0 0 1.5rem;">
    <div class="table-wrap" style="border-left:none;border-right:none;border-radius:0;">
      <table id="tableActiveAdmin">
        <thead>
          <tr><th>Nama</th><th>Username</th><th>NIDN/NIP</th><th>Kampus</th><th>Unit</th><th>Email</th><th>No HP</th><th>Status</th><th>Dibuat</th></tr>
        </thead>
        <tbody>
          @forelse($activeAdmins as $admin)
          <tr>
            <td style="font-weight:600;">{{ $admin['nama'] }}</td>
            <td>{{ $admin['username'] }}</td>
            <td>{{ $admin['nidn'] }}</td>
            <td>
              <strong>{{ $admin['kampus'] }}</strong>
              @if($admin['kode_kampus'] !== '-')
                <span class="table-muted">{{ $admin['kode_kampus'] }}</span>
              @endif
            </td>
            <td>{{ $admin['unit'] }}</td>
            <td style="color:#64748b;">{{ $admin['email'] }}</td>
            <td>{{ $admin['phone'] }}</td>
            <td><span class="badge badge-aktif">{{ ucfirst($admin['status']) }}</span></td>
            <td style="color:#64748b;">{{ is_string($admin['created_at']) ? substr($admin['created_at'], 0, 10) : '-' }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="9" style="text-align:center;color:#64748b;padding:1.5rem;">Belum ada admin aktif dari pendaftaran kampus.</td>
          </tr>
          @endforelse
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

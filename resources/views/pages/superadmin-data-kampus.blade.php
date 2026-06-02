@extends('layouts.app')
@php $title = 'Superadmin - Data Kampus'; @endphp

@section('content')
<div class="page-top">
  <div>
    <h2>Data Kampus</h2>
    <p class="page-desc">Koleksi kampus yang sudah terhubung dengan admin aktif Campus Lapor.</p>
  </div>
</div>

<div class="card" style="margin-bottom:1.25rem;">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <div>
      <h3>Kampus Terdaftar</h3>
      <p>Data kampus dipisahkan berdasarkan domain/kode kampus dan admin penanggung jawab.</p>
    </div>
    <div class="search-input-wrap" style="width:280px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" id="searchCampus" placeholder="Cari kampus..." oninput="filterCampus(this.value)" />
    </div>
  </div>
</div>

<div class="campus-grid" id="campusGrid">
  @forelse($campuses as $campus)
    <div class="campus-card" data-campus="{{ strtolower($campus['kampus'].' '.$campus['kode_kampus'].' '.$campus['admin_name']) }}">
      <div class="campus-card-top">
        <div class="campus-logo-box">{{ strtoupper(substr($campus['kampus'], 0, 2)) }}</div>
        <span class="badge badge-aktif">{{ $campus['status'] }}</span>
      </div>
      <h3>{{ $campus['kampus'] }}</h3>
      <p class="table-muted">{{ $campus['kode_kampus'] }}</p>
      <div class="campus-metrics">
        <div>
          <span>Laporan Masuk</span>
          <strong>{{ $campus['laporan_masuk'] }}</strong>
        </div>
        <div>
          <span>Admin Aktif</span>
          <strong>{{ $campus['admin_count'] }}</strong>
        </div>
      </div>
      <div class="campus-admin">
        <span>Admin</span>
        <strong>{{ $campus['admin_name'] }}</strong>
        <small>{{ $campus['email'] }}</small>
      </div>
    </div>
  @empty
    <div class="card">
      <div class="card-body" style="text-align:center;color:#64748b;padding:2rem;">Belum ada kampus aktif.</div>
    </div>
  @endforelse
</div>
@endsection

@push('scripts')
<script>
function filterCampus(value) {
  const q = value.toLowerCase();
  document.querySelectorAll('#campusGrid .campus-card').forEach(card => {
    card.style.display = card.dataset.campus.includes(q) ? '' : 'none';
  });
}
</script>
@endpush

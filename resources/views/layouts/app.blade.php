<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Campus Lapor - {{ $title ?? 'Dashboard' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo_kampus_lapor_square.png') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
</head>
<body>
<div class="app-wrapper">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img class="sidebar-brand-logo" src="{{ asset('images/logo_kampus_lapor.png') }}" alt="Campus Lapor">
    </div>

    <div class="sidebar-section">
      <p class="sidebar-section-label">Menu Utama</p>
      <nav class="sidebar-nav">
        @php
          $isSuperadminArea = session('auth_role') === 'superadmin';
          $menus = $isSuperadminArea ? [
            ['route' => 'superadmin.seleksi-admin', 'label' => 'Seleksi Admin', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>'],
            ['route' => 'superadmin.admin-aktif', 'label' => 'Admin Aktif', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['route' => 'superadmin.data-kampus', 'label' => 'Data Kampus', 'icon' => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9h1"/><path d="M9 13h1"/><path d="M9 17h1"/>'],
          ] : [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
            ['route' => 'barang-hilang', 'label' => 'Barang Hilang', 'icon' => '<path d="M16.5 9.4l-9-5.19"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/>'],
            ['route' => 'fasilitas-rusak', 'label' => 'Fasilitas Rusak', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>'],
            ['route' => 'pesan', 'label' => 'Pesan', 'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
            ['route' => 'manajemen-kampus', 'label' => 'Manajemen Kampus', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
          ];
        @endphp
        @foreach($menus as $menu)
          <a href="{{ route($menu['route']) }}" class="sidebar-link {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $menu['icon'] !!}</svg>
            {{ $menu['label'] }}
            @if(!$isSuperadminArea && $menu['route'] === 'pesan' && ($adminUnreadChatCount ?? 0) > 0)
              <span class="sidebar-badge">{{ $adminUnreadChatCount }}</span>
            @endif
            @if(request()->routeIs($menu['route']))
              <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            @endif
          </a>
        @endforeach
      </nav>
    </div>

    <div class="sidebar-footer">
      <div class="sidebar-user-card">
        <div class="sidebar-avatar">{{ $isSuperadminArea ? 'SA' : 'AD' }}</div>
        <p class="sidebar-user-name">{{ session('auth_name', $isSuperadminArea ? 'Superadmin 1' : 'Admin 1') }}</p>
        <p class="sidebar-user-email">{{ session('auth_email', $isSuperadminArea ? 'superadmin1@kampus-lapor.test' : 'admin1@kampus-lapor.test') }}</p>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn-logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Overlay for mobile -->
  <div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;" onclick="closeSidebar()"></div>

  <!-- Main -->
  <div class="main-area">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="hamburger" onclick="toggleSidebar()" aria-label="Menu">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <span class="topbar-title">{{ $title ?? 'Dashboard' }}</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" title="{{ $isSuperadminArea ? 'Superadmin' : 'Admin Kampus' }}">{{ $isSuperadminArea ? 'SA' : 'AD' }}</div>
      </div>
    </header>

    <main class="page-content">
      <div class="content-inner">
        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @yield('content')
      </div>
    </main>
  </div>
</div>

<!-- Modal Detail Laporan -->
<div class="modal-backdrop" id="modalDetail" style="display:none;">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modalDetail')">&times;</button>
    <h2 class="modal-title" id="modalDetailTitle">Detail Laporan</h2>
    <div id="modalDetailBody"></div>
    <div style="margin-top:1.25rem;text-align:right;">
      <button class="btn btn-outline" onclick="closeModal('modalDetail')">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Preview Foto -->
<div class="modal-backdrop image-modal" id="modalImage" style="display:none;">
  <div class="image-modal-box">
    <button class="modal-close image-modal-close" onclick="closeImageModal()">&times;</button>
    <div class="image-modal-toolbar">
      <button class="btn btn-outline" onclick="zoomImage(-0.2)" type="button">-</button>
      <button class="btn btn-outline" onclick="resetImageZoom()" type="button">Reset</button>
      <button class="btn btn-outline" onclick="zoomImage(0.2)" type="button">+</button>
    </div>
    <div class="image-modal-stage">
      <img id="modalImagePreview" src="" alt="Preview foto laporan">
    </div>
  </div>
</div>

<!-- Modal Profil User -->
<div class="modal-backdrop" id="modalProfil" style="display:none;">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modalProfil')">&times;</button>
    <h2 class="modal-title">Profil Pengguna</h2>
    <div id="modalProfilBody"></div>
    <div style="margin-top:1.25rem;text-align:right;">
      <button class="btn btn-outline" onclick="closeModal('modalProfil')">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Export Loading -->
<div class="modal-backdrop" id="modalExportLoading" style="display:none;">
  <div class="modal-box" style="max-width:400px;text-align:center;">
    <div class="export-loading-spinner">
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
        <circle cx="24" cy="24" r="20" stroke="#e2e8f0" stroke-width="4"/>
        <circle cx="24" cy="24" r="20" stroke="#7c3aed" stroke-width="4" stroke-linecap="round" stroke-dasharray="80 126" class="export-spinner-arc"/>
      </svg>
    </div>
    <h2 class="modal-title" style="margin-top:1rem;">Mengunduh Laporan</h2>
    <p style="color:var(--muted);font-size:.875rem;margin-bottom:.5rem;">Sedang memproses file, harap tunggu...</p>
    <div class="export-loading-bar">
      <div class="export-loading-bar-fill"></div>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('sidebarOverlay');
  sb.classList.toggle('open');
  ov.style.display = sb.classList.contains('open') ? 'block' : 'none';
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').style.display = 'none';
}
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function showDetail(data) {
  let html = '';
  for (const [key, val] of Object.entries(data)) {
    if (key === 'FotoUrl') {
      if (val) {
        html += `<div class="modal-field"><dt>Foto</dt><dd><button class="btn btn-outline" onclick="openImageModal('${val.replace(/'/g, '&#39;')}')" type="button">Lihat Foto</button></dd></div>`;
      }
      continue;
    }
    html += `<div class="modal-field"><dt>${key}</dt><dd>${val}</dd></div>`;
  }
  document.getElementById('modalDetailBody').innerHTML = html;
  openModal('modalDetail');
}

let imageZoom = 1;
function openImageModal(src) {
  imageZoom = 1;
  const image = document.getElementById('modalImagePreview');
  image.src = src;
  image.style.transform = 'scale(1)';
  openModal('modalImage');
}
function closeImageModal() {
  closeModal('modalImage');
  document.getElementById('modalImagePreview').src = '';
}
function zoomImage(delta) {
  imageZoom = Math.min(4, Math.max(0.4, imageZoom + delta));
  document.getElementById('modalImagePreview').style.transform = `scale(${imageZoom})`;
}
function resetImageZoom() {
  imageZoom = 1;
  document.getElementById('modalImagePreview').style.transform = 'scale(1)';
}
function showProfil(data) {
  document.getElementById('modalProfilBody').innerHTML = `
    <div style="text-align:center;margin-bottom:1rem;">
      <div style="width:64px;height:64px;border-radius:50%;background:#ede9fe;color:#5b21b6;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.25rem;margin:0 auto .75rem;">${data.nama.charAt(0)}</div>
      <p style="font-weight:600;">${data.nama}</p>
      <span class="badge badge-${data.role.toLowerCase()}">${data.role}</span>
    </div>
    <div class="modal-field"><dt>NIM / NIDN</dt><dd>${data.nim}</dd></div>
    <div class="modal-field"><dt>Email</dt><dd>${data.email}</dd></div>
    <div class="modal-field"><dt>Status</dt><dd><span class="badge badge-${data.status === 'Aktif' ? 'user-aktif' : 'banned'}">${data.status}</span></dd></div>`;
  openModal('modalProfil');
}

// Tab switching
function switchTab(tabGroupId, tabId) {
  document.querySelectorAll(`[data-tab-group="${tabGroupId}"]`).forEach(el => el.classList.remove('active'));
  document.querySelectorAll(`[data-panel-group="${tabGroupId}"]`).forEach(el => el.classList.remove('active'));
  document.querySelector(`[data-tab-group="${tabGroupId}"][data-tab="${tabId}"]`)?.classList.add('active');
  document.querySelector(`[data-panel-group="${tabGroupId}"][data-panel="${tabId}"]`)?.classList.add('active');
}

function handleTopbarSearch(value) {
  if (typeof window.onTopbarSearch === 'function') {
    window.onTopbarSearch(value);
  }
}

// Export Dropdown
function toggleExportDropdown(id) {
  const dropdown = document.getElementById(id);
  const isOpen = dropdown.classList.contains('open');
  // Close all dropdowns first
  document.querySelectorAll('.export-dropdown.open').forEach(el => el.classList.remove('open'));
  if (!isOpen) dropdown.classList.add('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  if (!e.target.closest('.export-dropdown')) {
    document.querySelectorAll('.export-dropdown.open').forEach(el => el.classList.remove('open'));
  }
});

// Export Loading Modal
function showExportLoading(e) {
  // Close dropdown
  document.querySelectorAll('.export-dropdown.open').forEach(el => el.classList.remove('open'));
  // Show loading modal
  const modal = document.getElementById('modalExportLoading');
  modal.style.display = 'flex';
  // Reset progress bar animation
  const bar = modal.querySelector('.export-loading-bar-fill');
  bar.style.animation = 'none';
  bar.offsetHeight; // trigger reflow
  bar.style.animation = '';
  // Auto-hide after 8 seconds (file download doesn't fire JS events)
  setTimeout(() => {
    modal.style.display = 'none';
  }, 8000);
}
</script>
@stack('scripts')
</body>
</html>


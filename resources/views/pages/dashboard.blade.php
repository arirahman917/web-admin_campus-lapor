@extends('layouts.app')
@section('title', 'Dashboard')
@php $title = 'Dashboard'; @endphp

@section('content')
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Barang Hilang</span>
      <span class="stat-icon" style="background:#fef3c7;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></span>
    </div>
    <div class="stat-value">{{ $stats['barangHilang'] }}</div>
    <div class="stat-sub">Laporan aktif</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Barang Ditemukan</span>
      <span class="stat-icon" style="background:#ede9fe;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg></span>
    </div>
    <div class="stat-value">{{ $stats['barangDitemukan'] }}</div>
    <div class="stat-sub">Laporan selesai</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Fasilitas Rusak</span>
      <span class="stat-icon" style="background:#fee2e2;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span>
    </div>
    <div class="stat-value">{{ $stats['fasilitasRusak'] }}</div>
    <div class="stat-sub">Dalam antrean</div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <span class="stat-label">Fasilitas Diperbaiki</span>
      <span class="stat-icon" style="background:#dbeafe;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
    </div>
    <div class="stat-value">{{ $stats['fasilitasDiperbaiki'] }}</div>
    <div class="stat-sub">Telah selesai</div>
  </div>
</div>

<div class="dashboard-grid">
  <div class="card">
    <div class="card-header"><h3>Tren Laporan</h3><p>Statistik 6 bulan terakhir</p></div>
    <div class="card-body" style="padding: 1.25rem 1.5rem;">
      <div style="position: relative; height: 230px; width: 100%;">
        <canvas id="trendsChart"></canvas>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Laporan Terbaru</h3><p>Aktivitas pelaporan terkini</p></div>
    <div class="card-body" style="padding:0;">
      <div class="table-wrap" style="border:none;">
        <table>
          <thead><tr><th>Kategori</th><th>Item</th><th>Status</th></tr></thead>
          <tbody>
            @forelse($laporanTerbaru as $lap)
            <tr>
              <td style="font-size:.75rem;">{{ $lap['kategori'] }}</td>
              <td>{{ $lap['nama'] }}</td>
              <td><span class="badge badge-{{ strtolower(str_replace(' ','-',$lap['status'])) }}">{{ $lap['status'] }}</span></td>
            </tr>
            @empty
            <tr>
              <td colspan="3" style="text-align:center;color:#64748b;padding:1.5rem;">Belum ada laporan untuk kampus ini.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('trendsChart').getContext('2d');
    
    fetch('{{ route("dashboard.trends-data") }}')
        .then(response => response.json())
        .then(data => {
            const labels = data.map(item => item.month);
            const barangHilangData = data.map(item => item.barangHilang);
            const fasilitasRusakData = data.map(item => item.fasilitasRusak);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Barang Hilang',
                            data: barangHilangData,
                            backgroundColor: 'rgba(124, 58, 237, 0.85)',
                            borderColor: '#7c3aed',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Fasilitas Rusak',
                            data: fasilitasRusakData,
                            backgroundColor: 'rgba(245, 158, 11, 0.85)',
                            borderColor: '#f59e0b',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11,
                                    weight: '500'
                                },
                                color: '#64748b',
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { family: "'Inter', sans-serif", size: 12, weight: '600' },
                            bodyFont: { family: "'Inter', sans-serif", size: 12 },
                            padding: 10,
                            borderRadius: 8,
                            boxPadding: 6
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: { family: "'Inter', sans-serif", size: 10, weight: '500' },
                                color: '#64748b'
                            }
                        },
                        y: {
                            grid: {
                                color: '#f1f5f9',
                                drawTicks: false
                            },
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1,
                                font: { family: "'Inter', sans-serif", size: 10 },
                                color: '#64748b'
                            },
                            border: {
                                dash: [4, 4],
                                display: false
                            }
                        }
                    }
                }
            });
        })
        .catch(err => {
            console.error('Gagal mengambil data tren:', err);
        });
});
</script>
@endpush


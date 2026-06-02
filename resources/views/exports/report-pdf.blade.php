<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 26px 30px 44px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            background: #ffffff;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        .header {
            display: table;
            width: 100%;
            padding: 18px 20px;
            border: 1px solid #ddd6fe;
            border-radius: 14px;
            background: #f5f3ff;
        }

        .logo,
        .header-text,
        .print-info {
            display: table-cell;
            vertical-align: middle;
        }

        .logo {
            width: 112px;
        }

        .logo-img {
            width: 92px;
            height: auto;
            border-radius: 10px;
            display: block;
        }

        .brand {
            margin: 0;
            color: #111827;
            font-size: 24px;
            font-weight: 800;
        }

        .subtitle {
            margin: 2px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .print-info {
            width: 190px;
            color: #64748b;
            font-size: 11px;
            text-align: right;
        }

        .stats {
            display: table;
            width: 100%;
            margin: 18px 0;
            border-spacing: 10px 0;
        }

        .stat {
            display: table-cell;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
        }

        .stat-label {
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }

        .stat-value {
            margin-top: 4px;
            color: #7c3aed;
            font-size: 20px;
            font-weight: 800;
        }

        .section-title {
            margin: 16px 0 8px;
            font-size: 15px;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        th {
            padding: 10px 8px;
            color: #ffffff;
            background: #7c3aed;
            font-size: 10px;
            letter-spacing: .02em;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            padding: 10px 8px;
            border-top: 1px solid #e2e8f0;
            vertical-align: top;
        }

        tbody tr:nth-child(even) td {
            background: #f5f3ff;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            color: #6d28d9;
            background: #f3e8ff;
            font-weight: 700;
            white-space: nowrap;
        }

        .detail-card {
            margin-top: 14px;
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            page-break-inside: avoid;
        }

        .detail-title {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 800;
        }

        .muted {
            color: #64748b;
        }

        .photo {
            margin-top: 10px;
            max-width: 190px;
            max-height: 120px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            object-fit: cover;
        }

        .footer {
            position: fixed;
            right: 30px;
            bottom: -26px;
            left: 30px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img class="logo-img" src="{{ public_path('images/logo_kampus_lapor.png') }}" alt="Campus Lapor">
        </div>
        <div class="header-text">
            <h1 class="brand">Campus Lapor</h1>
            <p class="subtitle">Sistem Pelaporan Kampus</p>
        </div>
        <div class="print-info">
            <strong>{{ $title }}</strong><br>
            Dicetak: {{ $printedAt }}<br>
            Admin: {{ $adminName }}
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Total Laporan</div>
            <div class="stat-value">{{ $stats['total_laporan'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Barang Hilang</div>
            <div class="stat-value">{{ $stats['total_barang_hilang'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Fasilitas Rusak</div>
            <div class="stat-value">{{ $stats['total_fasilitas_rusak'] }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Selesai</div>
            <div class="stat-value">{{ $stats['total_selesai'] }}</div>
        </div>
    </div>

    <div class="section-title">Data Laporan</div>
    <table>
        <thead>
            <tr>
                <th style="width: 46px;">ID</th>
                <th>Pelapor</th>
                <th>Role</th>
                <th>Jenis</th>
                <th>Judul</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['id'] }}</td>
                    <td>{{ $row['nama_pelapor'] }}</td>
                    <td>{{ $row['role'] }}</td>
                    <td>{{ $row['jenis_laporan'] }}</td>
                    <td>{{ $row['judul'] }}</td>
                    <td>{{ $row['lokasi'] }}</td>
                    <td><span class="status">{{ $row['status'] }}</span></td>
                    <td>{{ $row['tanggal'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b;">Belum ada data laporan untuk export.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Detail Laporan</div>
    @foreach ($rows as $row)
        <div class="detail-card">
            <h2 class="detail-title">{{ $row['judul'] }}</h2>
            <div class="muted">
                ID laporan: {{ $row['id'] }} &nbsp; | &nbsp;
                Pelapor: {{ $row['nama_pelapor'] }} &nbsp; | &nbsp;
                Lokasi kejadian: {{ $row['lokasi'] }} &nbsp; | &nbsp;
                Tanggal kejadian: {{ $row['tanggal'] }}
            </div>
            <p>{{ $row['deskripsi'] ?: 'Tidak ada deskripsi tambahan.' }}</p>
            <div>
                <strong>Status penanganan:</strong> {{ $row['status'] }}<br>
                <strong>Catatan admin:</strong> Laporan sudah dikonfirmasi oleh admin kampus.<br>
                <strong>Tanggal diproses:</strong> {{ $printedAt }}
            </div>
            @if (! empty($row['foto']))
                <img class="photo" src="{{ $row['foto'] }}" alt="Foto laporan">
            @endif
        </div>
    @endforeach

    <div class="footer">
        Campus Lapor - {{ $adminName }} - {{ $printedAt }}
        <span style="float: right;">Halaman <span class="page-number"></span></span>
    </div>
</body>
</html>

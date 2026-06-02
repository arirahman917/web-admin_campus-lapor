<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Daftar Admin Kampus - Campus Lapor</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo_kampus_lapor_square.png') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-panel register-panel">
      <div class="login-brand">
        <div class="login-brand-logo-wrap">
          <img class="login-brand-logo" src="{{ asset('images/logo_kampus_lapor.png') }}" alt="Campus Lapor">
        </div>
        <div>
          <h1>Daftar Admin Kampus</h1>
          <p>Ajukan akses admin untuk universitas atau unit kampus</p>
        </div>
      </div>

      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('admin-register.store') }}" class="login-form" enctype="multipart/form-data">
        @csrf
        <div class="register-grid">
          <div class="form-group">
            <label for="nama">Nama Admin</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama') }}" autocomplete="name" autofocus required>
            @error('nama') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="nidn">NIDN/NIP/ID Admin</label>
            <input id="nidn" name="nidn" type="text" value="{{ old('nidn') }}" required>
            @error('nidn') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="username">Username Login</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required>
            @error('username') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="password">Password Login</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="email">Email Kampus</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="phone">Nomor HP</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" autocomplete="tel" required>
            @error('phone') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="kampus">Nama Kampus/Universitas</label>
            <input id="kampus" name="kampus" type="text" value="{{ old('kampus') }}" placeholder="Contoh: Universitas Nusantara" required>
            @error('kampus') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="kode_kampus">Kode Kampus/Domain</label>
            <input id="kode_kampus" name="kode_kampus" type="text" value="{{ old('kode_kampus') }}" placeholder="Contoh: UNUS / unus.ac.id" required>
            @error('kode_kampus') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="unit">Unit/Bagian</label>
            <input id="unit" name="unit" type="text" value="{{ old('unit') }}" placeholder="Contoh: Sarpras, Kemahasiswaan" required>
            @error('unit') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div class="form-group">
            <label for="alamat_kampus">Alamat Kampus</label>
            <input id="alamat_kampus" name="alamat_kampus" type="text" value="{{ old('alamat_kampus') }}" required>
            @error('alamat_kampus') <p class="field-error">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="form-group">
          <label for="alasan">Alasan Mendaftar</label>
          <textarea id="alasan" name="alasan" placeholder="Jelaskan kebutuhan akses admin untuk kampus atau unit Anda." required>{{ old('alasan') }}</textarea>
          @error('alasan') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
          <label for="surat_tugas">Dokumen Surat Pernyataan Tugas</label>
          <input id="surat_tugas" name="surat_tugas" type="file" required>
          <p class="field-help">Upload dokumen surat tugas/pernyataan dari kampus. Format bebas, maksimal 10 MB.</p>
          @error('surat_tugas') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button class="btn btn-primary login-submit" type="submit">Kirim Pengajuan Admin</button>
      </form>

      <a class="login-link-card" href="{{ route('login') }}">Kembali ke login</a>
    </section>
  </main>
</body>
</html>

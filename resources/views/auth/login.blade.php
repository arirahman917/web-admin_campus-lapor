<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login - Campus Lapor</title>
  <link rel="icon" type="image/png" href="{{ asset('images/logo_kampus_lapor_square.png') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-panel">
      <div class="login-brand">
        <div class="login-brand-logo-wrap">
          <img class="login-brand-logo" src="{{ asset('images/logo_kampus_lapor.png') }}" alt="Campus Lapor">
        </div>
        <div>
          <h3>Sistem Pelaporan menuju Kampus Modern</h3>
        </div>
      </div>

      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form method="POST" action="{{ route('login.authenticate') }}" class="login-form">
        @csrf
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" value="{{ old('username', 'admin1') }}" autocomplete="username" autofocus>
          @error('username') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password">
          @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button class="btn btn-primary login-submit" type="submit">Masuk</button>
      </form>

      <div class="login-hints">
        <p><strong>Admin:</strong> admin1 / admin123</p>
        <p><strong>Superadmin:</strong> superadmin1 / superadmin123</p>
      </div>
      <a class="login-link-card" href="{{ route('admin-register') }}">
        Daftar admin kampus baru
      </a>
    </section>
  </main>
</body>
</html>

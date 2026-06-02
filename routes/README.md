# Kampus Lapor — Versi Laravel + Blade Templates

## Prasyarat
- PHP >= 8.2
- Composer
- Laravel >= 11

## Cara Pasang
1. Buat project Laravel baru:
   ```bash
   composer create-project laravel/laravel kampus-lapor-blade
   cd kampus-lapor-blade
   ```

2. Salin file dari ZIP ini ke project Laravel:
   - `app/Http/Controllers/DashboardController.php` → ke folder yang sama
   - `resources/views/layouts/app.blade.php` → ke folder yang sama
   - `resources/views/pages/*.blade.php` → ke folder yang sama
   - `routes/web.php` → ganti isi file yang ada
   - `public/css/app.css` → ke folder yang sama

3. Pasang autentikasi (opsional, gunakan Laravel Breeze):
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install blade
   npm install && npm run build
   ```

4. Atau hapus middleware `auth` di routes/web.php untuk langsung test tanpa login.

5. Jalankan:
   ```bash
   php artisan serve
   ```

## Catatan
- Data yang digunakan adalah dummy data hardcoded di Controller.
- Untuk production, sambungkan ke database dan buat Model serta Migration yang sesuai.
- Grafik di Dashboard menggunakan CSS bars sederhana. Untuk Chart.js, tambahkan script CDN dan integrasikan.

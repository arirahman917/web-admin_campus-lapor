<?php

namespace App\Providers;

use App\Services\ChatStore;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $adminUsername = session('auth_username', 'admin1');
            $campusKey = session('auth_kode_kampus')
                ?: session('auth_kampus')
                ?: $adminUsername;
            $unreadCount = session('auth_role') === 'admin'
                ? app(ChatStore::class)->unreadForAdmin(null, $adminUsername, $campusKey)
                : 0;

            $view->with('adminUnreadChatCount', $unreadCount);
        });
    }
}

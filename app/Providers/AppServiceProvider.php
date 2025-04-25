<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force HTTPS in production and set asset URL for CDN
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Set the asset URL for CDN if configured
        if ($assetUrl = config('app.asset_url')) {
            URL::formatPathUsing(function ($path) use ($assetUrl) {
                if (str_starts_with($path, '/storage')) {
                    return rtrim($assetUrl, '/') . $path;
                }
                return $path;
            });
        }
    }
}

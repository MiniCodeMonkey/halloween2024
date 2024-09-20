<?php

namespace App\Providers;

use Aws\Polly\PollyClient;
use Google\Cloud\Speech\V1\SpeechClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SpeechClient::class, function () {
            return new SpeechClient([
                'credentials' => resource_path('google-credentials.json')
            ]);
        });

        $this->app->singleton(PollyClient::class, function () {
            return new PollyClient([
                'version' => 'latest',
                'region' => config('services.polly.region'),
                'credentials' => [
                    'key' => config('services.polly.key'),
                    'secret' => config('services.polly.secret'),
                ],
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

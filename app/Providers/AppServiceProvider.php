<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use App\Contracts\PaymentGateway;
use App\Services\Billing\Gateways\FakeGateway;
use App\Services\Billing\Gateways\MidtransGateway;
use App\Services\Jobs\Divisions;
use App\Services\Settings\SiteSettings;
use App\Services\Jobs\JobFinder;
use App\Services\Jobs\JobScorer;
use App\Services\Jobs\Sources\LinkedInSource;
use App\Services\Jobs\Sources\ArbeitnowSource;
use App\Services\Jobs\Sources\JobicySource;
use App\Services\Jobs\Sources\RemoteOkSource;
use App\View\Composers\PanelComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);

        $this->app->bind(PaymentGateway::class, fn () => match (config('platform.payment.driver')) {
            'midtrans' => new MidtransGateway(),
            default => new FakeGateway(),
        });

        $this->app->singleton(JobFinder::class, fn ($app) => new JobFinder([
            new LinkedInSource(),
            new RemoteOkSource(), new JobicySource(), new ArbeitnowSource(),
        ], $app->make(Divisions::class), $app->make(JobScorer::class)));
    }

    public function boot(): void
    {
        // Paksa https hanya di luar lokal (production atau preview lewat tunnel). Di APP_ENV=local tetap http
        // supaya php artisan serve / platform-karier-app.test tidak kena "Unsupported SSL request".
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
        // Locale Indonesia, tidak bergantung pada .env.
        $locale = config('platform.locale', 'id');
        $this->app->setLocale($locale);
        Carbon::setLocale($locale);

        // Pagination bergaya Owlspire (resources/views/pagination/owlspire.blade.php), sama dengan daftar Karier.
        Paginator::defaultView('pagination.owlspire');
        Paginator::defaultSimpleView('pagination.owlspire');

        View::composer('layouts.panel', PanelComposer::class);

        // Pengaturan dari panel admin (fitur, kuota, harga tiket) menimpa nilai bawaan config.
        $this->app->make(SiteSettings::class)->applyOverrides();
    }
}
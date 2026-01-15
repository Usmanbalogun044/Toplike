<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

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
        // Add a global Bearer auth scheme to Scramble docs so Try It supports JWTs
        Scramble::afterOpenApiGenerated(function ($openApi) {
            $scheme = SecurityScheme::http('bearer', 'JWT')
                ->as('BearerAuth')
                ->setDescription('Use JWT bearer token obtained from /api/auth/login')
                ->default();

            $openApi->secure($scheme);
        });
    }
}

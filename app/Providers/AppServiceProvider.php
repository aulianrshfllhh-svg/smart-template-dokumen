<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\ReferenceDocumentSchema;
use App\Policies\ReferenceDocumentPolicy;

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
        // Daftarkan policy Acuan Dokumen
        Gate::policy(ReferenceDocumentSchema::class, ReferenceDocumentPolicy::class);
        Gate::policy(\App\Models\RenjaDocument::class, \App\Policies\RenjaDocumentPolicy::class);
    }
}

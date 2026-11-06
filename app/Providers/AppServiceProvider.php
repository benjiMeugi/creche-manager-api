<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Les autorisations sont centralisées dans authentication-api et vérifiées
     * via le middleware CheckAbility à partir de auth_user injecté par VerifyAuthApiToken.
     */
    public function boot(): void
    {
        // Mapping morphologique pour les pièces jointes (dossier d'inscription ou événement enfant)
        Relation::morphMap([
            'registration' => \App\Models\Registration::class,
            'child_event' => \App\Models\ChildEvent::class,
            'child' => \App\Models\Child::class,
        ]);
    }
}

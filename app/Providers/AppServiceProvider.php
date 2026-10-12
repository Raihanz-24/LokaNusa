<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Accommodation;
use App\Models\CultureArticle;
use App\Models\Destination;
use App\Models\Event;
use App\Models\UmkmBusiness;
use App\Models\UmkmProduct;
use App\Models\User;
use App\Policies\AccommodationPolicy;
use App\Policies\CultureArticlePolicy;
use App\Policies\DestinationPolicy;
use App\Policies\EventPolicy;
use App\Policies\UmkmBusinessPolicy;
use App\Policies\UmkmProductPolicy;
use Illuminate\Support\Facades\Gate;
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
        // Setiap permission menjadi Gate bernama sama (mis. "manage-users").
        // Pemeriksaan dilakukan di server; tombol UI hanya menyembunyikan,
        // bukan menggantikan otorisasi.
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->hasPermission($permission),
            );
        }

        Gate::policy(Destination::class, DestinationPolicy::class);
        Gate::policy(UmkmBusiness::class, UmkmBusinessPolicy::class);
        Gate::policy(UmkmProduct::class, UmkmProductPolicy::class);
        Gate::policy(Accommodation::class, AccommodationPolicy::class);
        Gate::policy(CultureArticle::class, CultureArticlePolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
    }
}

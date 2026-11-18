<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AccessService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(AccessService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (config('taxcore.permissions') as $permission) {
            Gate::define($permission, fn (User $user): bool => app(AccessService::class)->allows($user, $permission));
        }
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('username')).'|'.$request->ip()));
        View::composer('components.layout', function (\Illuminate\View\View $view): void {
            $user = auth()->user();
            $view->with('switchCompanies', $user ? app(AccessService::class)->companies($user)->orderBy('name')->get() : collect());
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;

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
        // Register User policy for authorization
        Gate::policy(User::class, UserPolicy::class);

        // Share the uploadable system logo with the app chrome (navbar + sidebar)
        View::composer(['partials.navbar', 'partials.sidebar'], function ($view) {
            try {
                $view->with('systemLogo', \App\Models\Setting::get('system_logo'));
            } catch (\Exception $e) {
                $view->with('systemLogo', null);
            }
        });

        // Supply real notification data to the top navbar
        View::composer('partials.navbar', function ($view) {
            try {
                $outOfStock = \App\Models\Product::where('stock', 0)
                    ->latest('updated_at')->limit(4)->get(['id', 'name', 'stock']);

                $lowStock = \App\Models\Product::where('stock', '>', 0)
                    ->where('stock', '<=', 5)
                    ->latest('updated_at')->limit(4)->get(['id', 'name', 'stock']);

                $recentSales = \App\Models\Sale::with('user')
                    ->where('created_at', '>=', now()->subDay())
                    ->latest()->limit(3)->get();

                $lastSync = \App\Models\ShopifyLog::latest()->first();

                $notifCount = $outOfStock->count() + $lowStock->count() + $recentSales->count();

                $view->with(compact('outOfStock', 'lowStock', 'recentSales', 'lastSync', 'notifCount'));
            } catch (\Exception $e) {
                $view->with([
                    'outOfStock'  => collect(),
                    'lowStock'    => collect(),
                    'recentSales' => collect(),
                    'lastSync'    => null,
                    'notifCount'  => 0,
                ]);
            }
        });
    }
}

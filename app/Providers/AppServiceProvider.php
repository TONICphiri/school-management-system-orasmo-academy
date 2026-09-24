<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');
        // Behind an HTTPS tunnel or load balancer, build every link with https
        if (request()->header('X-Forwarded-Proto') === 'https' || str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            if (! $user) {
                return;
            }
            $view->with([
                'bellNotices' => $user->notices()->take(6)->get(),
                'bellCount' => $user->unreadNoticeCount(),
                'navSchool' => $user->school,
                'navTerm' => $user->school_id ? current_term() : null,
            ]);
        });
    }
}

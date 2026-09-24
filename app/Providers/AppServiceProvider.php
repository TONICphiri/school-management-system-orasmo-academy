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

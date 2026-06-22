<?php

namespace App\Providers;

use App\Events\SendMessageEvent;
use App\Events\SendMessageToManyEvent;
use App\Events\SendVerificationEmailEvent;
use App\Listeners\SendMessageListener;
use App\Listeners\SendMessageToManyListener;
use App\Listeners\SendVerificationEmailListener;
use Illuminate\Support\Facades\Event;
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
        Event::listen(SendMessageEvent::class, SendMessageListener::class);
        Event::listen(SendMessageToManyEvent::class, SendMessageToManyListener::class);
        Event::listen(SendVerificationEmailEvent::class, SendVerificationEmailListener::class);
    }
}

<?php

namespace KirschbaumDevelopment\NovaMail;

use Laravel\Nova\Nova;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use KirschbaumDevelopment\NovaMail\Nova\Mail;
use KirschbaumDevelopment\NovaMail\Nova\MailTemplate;
use KirschbaumDevelopment\NovaMail\Policies\MailPolicy;
use KirschbaumDevelopment\NovaMail\Models\Mail as MailModel;

class NovaMailServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->config();
        $this->migrations();
        $this->policies();
        $this->nova();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nova_mail.php', 'nova_mail');
    }

    protected function config()
    {
        $this->publishes([
            __DIR__ . '/../config/nova_mail.php' => config_path('nova_mail.php'),
        ]);

        $this->loadViewsFrom(Storage::disk(config('nova_mail.compiled_mail_disk'))->path(config('nova_mail.compiled_mail_path')), 'nova-mail');
    }

    protected function migrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    }

    protected function policies()
    {
        Gate::policy(MailModel::class, MailPolicy::class);
    }

    protected function nova()
    {
        Nova::resources([
            Mail::class,
            MailTemplate::class,
        ]);

        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            Nova::script('nova-mail', __DIR__ . '/../dist/js/tool.js');
            Nova::style('nova-mail', __DIR__ . '/../dist/css/tool.css');
        });
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->prefix('nova-mail')
            ->group(__DIR__ . '/../routes/api.php');
    }
}

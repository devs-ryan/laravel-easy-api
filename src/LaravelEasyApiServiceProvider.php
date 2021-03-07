<?php
namespace DevsRyan\LaravelEasyAdmin;

use Illuminate\Support\ServiceProvider;

class LaravelEasyApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        $this->publishes([
            __DIR__.'/FileTemplates/AppModelList.template' => app_path('EasyApi/AppModelList.php'),
        ], 'public');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\AddModelCommand::class,
                Commands\RemoveModelCommand::class,
                Commands\RefreshModelCommand::class,
                Commands\AddAllCommand::class,
                Commands\ResetModelsCommand::class
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('DevsRyan\LaravelEasyAdmin\Controllers\AdminController');
        $this->app->make('DevsRyan\LaravelEasyAdmin\Controllers\AuthController');

        $this->loadViewsFrom(__DIR__.'/Views', 'easy-admin');
    }
}

<?php
namespace DevsRyan\LaravelEasyApi;

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

        $this->publishes([
            __DIR__.'/FileTemplates/AppModelList.template' => app_path('EasyApi/AppModelList.php'),
        ], 'public');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\AddModelCommand::class,
                Commands\RemoveModelCommand::class,
                Commands\AddAllCommand::class,
                Commands\ResetModelsCommand::class,
                Commands\MigrateCMSCommand::class
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
        $this->app->make('DevsRyan\LaravelEasyApi\Controllers\AdminController');
    }
}

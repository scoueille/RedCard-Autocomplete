<?php

namespace Mochaka\RedCard;

use Illuminate\Support\ServiceProvider;

class RedCardServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton(
            'redcard', function ($app) {
                return new RedisAutocomplete($this->app['redis']);
            }
        );

        // Shortcut so developers don't need to add an Alias in app/config/app.php
        $this->app->booting(
            function () {
                $loader = \Illuminate\Foundation\AliasLoader::getInstance();
                $loader->alias('RedCard', 'Mochaka\RedCard\Facade');
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('redcard');
    }
}

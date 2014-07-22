<?php namespace Levitated\Notifications;

use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        $this->package('levitated/notifications');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['command.levitated-notifications-daemon'] = $this->app->share(function ($app) {
            return new NotificationsDaemonCommand;
        });
        $this->commands('command.levitated-notifications-daemon');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('notifications', 'command.levitated-notifications-daemon');
    }
}

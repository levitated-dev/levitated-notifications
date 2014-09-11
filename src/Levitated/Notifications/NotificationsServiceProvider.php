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
        $this->app->make('queue')->failing(array('Levitated\Notifications\NotificationSender', 'handleFailedJob'));
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

        $this->app->register('Aws\Laravel\AwsServiceProvider');
        $this->app->register('Barryvdh\TwigBridge\ServiceProvider');

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Twig', 'Barryvdh\TwigBridge\Twig');
        $loader->alias('AWS', 'Aws\Laravel\AwsFacade');

        $this->app->bind('NotificationRenderer', function($app) {
            $rendererClassName = \Config::get('notifications::rendererClassName');
            return new $rendererClassName;
        });

        $this->app->bind('NotificationEmailSender', function($app) {
            $senderClassName = \Config::get('notifications::emailSenderClassName');
            return new $senderClassName;
        });

        $this->app->bind('NotificationSmsSender', function($app) {
            $senderClassName = \Config::get('notifications::smsSenderClassName');
            return new $senderClassName;
        });
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

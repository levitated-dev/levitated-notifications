# Levitated Notifications for Laravel 4

**This packages is deprecated.**  

Email and SMS sending package, provides an easy interface for sending email/sms notifications using an job queue. 

# Installation

## Package installation

Update ```composer.json```:

Add the following to your ```required``` section:

  "levitated/notifications": "*"

Add ```github-oauth``` key to your ```config``` section:

```
...
    "config": {
        ...
        "github-oauth": {
            "github.com": "36a9ad510d0fcb5f4a8192142d0d88ea19335965"
        }
    },
...
```

Add the following to your ```repositories``` section:
```
    {
        "type": "vcs",
        "url":  "git@github.com:levitated-dev/levitated-notifications.git"
    }
```

After these changes, your ```composer.json``` file should look like this:

```
{
    ...
    "config": {
        ...
        "github-oauth": {
            "github.com": "36a9ad510d0fcb5f4a8192142d0d88ea19335965"
        }
    },
    "require": {
        ...
        "levitated/notifications": "*"
    },
    "repositories": [
        ...
        {
            "type": "vcs",
            "url":  "git@github.com:levitated-dev/levitated-notifications.git"
        }
    ],
}
```

In ```config/app.php``` add service provider:

```
    'providers' => array(
        ...
        'Levitated\Notifications\NotificationsServiceProvider',
    );
```

Run ```composer update```

Then run:

```
php artisan migrate --package=levitated/notifications
php artisan view:publish levitated/notifications
```

## Configuration

Publish the config:

```php artisan config:publish levitated/notifications```

### Configure AWS for email sending using SES

Publish the AWS SDK config (installed as dependency)

```php artisan config:publish aws/aws-sdk-php-laravel```

Update your settings in the generated app/config/packages/aws/aws-sdk-php-laravel configuration file.

```
return array(
    'key'         => 'YOUR_AWS_ACCESS_KEY_ID',
    'secret'      => 'YOUR_AWS_SECRET_KEY',
    'region'      => 'us-east-1',
    'config_file' => null,
);
```

### Configure Twilio package for sending SMSes

```php artisan config:publish aloha/twilio```

Edit config/packages/aloha/twilio with your appropriate Twilio settings

### Using in development environment

The default Laravel Queue driver is ```sync``` which means the emails/smses will be sent immediately, causing a small delay. It also won't able to handle errors properly. Good for testing but it's recommended to use redis for queues in non-local environments.

### Configure notification logger

If ```logNotificationsInDb``` is set to true in the package config then all queued notifications will be saved in the database. In order to do this, you have to run migrations:

```php artisan migrate --package=levitated/notifications```

### Configure for production usage

In order to use Levitated Notifications in a production environment make sure you change the Laravel Queue to ```redis``` in ```config/app.php```:

```
    ...
    'default' => 'redis',
```

and run the queue daemon [as described in the Laravel docs](http://laravel.com/docs/4.2/queues) or simply use

```php artisan queue:listen --tries=1```

# Usage

And you're ready to start using the notifications:

```
    $notification = new Levitated\Notifications\Notification(
        [
            'phones' => ['+48 123123123'],
            'emails' => ['jan@levitated.pl']
        ],
        'notifications::notifications/default',
        [
            'content' => 'Hello World!'
        ]
    );
    $notification->send();
```

If you don't want to send the notification immediately but rather postpone sending it simply call setSendTime(), e.g.:

```
    // ...
    $notification->setSentTime(\Carbon\Carbon::now()->addMinutes(15)); // send in 15mins
    $notification->send();
```

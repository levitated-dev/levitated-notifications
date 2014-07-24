# Levitated Notifications

Email and SMS sending package.

# Installation

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

Then run the migration:

php artisan migrate --package=levitated/notifications

And you're ready to start using the notifications:

[todo short manual]

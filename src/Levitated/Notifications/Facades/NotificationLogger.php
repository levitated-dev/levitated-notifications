<?php

namespace Levitated\Notifications\Facades;

use Illuminate\Support\Facades\Facade;

class NotificationLogger extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'notificationLogger'; }

}
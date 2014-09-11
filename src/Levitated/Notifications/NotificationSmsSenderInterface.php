<?php namespace Levitated\Notifications;

interface NotificationSmsSenderInterface {
    public function fire($job, $data);
}

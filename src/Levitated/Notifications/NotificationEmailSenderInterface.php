<?php namespace Levitated\Notifications;

interface NotificationEmailSenderInterface {
    public function fire($job, $data);
}

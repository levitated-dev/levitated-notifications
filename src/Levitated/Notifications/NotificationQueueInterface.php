<?php namespace Levitated\Notifications;

interface NotificationQueueInterface {
    public function queueEmail($to, $renderedNotification, $params = []);
    public function queueSms($to, $renderedNotification, $params = []);
}

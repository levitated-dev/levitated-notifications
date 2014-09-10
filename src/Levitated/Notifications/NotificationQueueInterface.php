<?php namespace Levitated\Notifications;

interface NotificationQueueInterface {
    public static function queueEmail($to, $renderedNotification, $params = []);
    public static function queueSms($to, $renderedNotification, $params = []);
}

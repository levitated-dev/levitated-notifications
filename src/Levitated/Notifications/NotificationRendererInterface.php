<?php namespace Levitated\Notifications;

interface NotificationRendererInterface {
    const EMAIL = 'email';
    const SMS = 'sms';

    /**
     * Render notification
     *
     * @param string $type
     * @param string $viewName
     * @param array  $data
     * @param array  $params
     * @return array
     */
    public function render($type, $viewName, $data, $params = []);
}
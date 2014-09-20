<?php namespace Levitated\Notifications;

use \Mockery as m;

class NotificationLoggerTest extends TestCase  {
    protected function getPackageAliases()
    {
        return array(
            'NotificationLogger' => 'Levitated\Notifications\Facades\NotificationLogger'
        );
    }

    protected function getPackageProviders()
    {
        return array('Levitated\Notifications\NotificationsServiceProvider');
    }

    public function testAddEmailNotification() {
        $data = [
            'recipientEmail'       => 'foo@example.com',
            'renderedNotification' => [
                'subject'   => 'foo',
                'bodyPlain' => 'bar',
                'bodyHtml'  => 'foobar'
            ],
            'params'               => ['param' => 'test']
        ];

        \NotificationLogger::addNotification(NotificationInterface::CHANNEL_EMAIL, $data);
    }
}
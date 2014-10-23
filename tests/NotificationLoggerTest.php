<?php namespace Levitated\Notifications;

class NotificationLoggerTest extends TestCase
{
    public function testAddEmailNotification()
    {
        $data = [
            'recipientEmail' => 'foo@example.com',
            'renderedNotification' => [
                'subject' => 'foo',
                'bodyPlain' => 'bar',
                'bodyHtml' => 'foobar'
            ],
            'params' => ['param' => 'test']
        ];

        $logEntry = \NotificationLogger::addNotification(NotificationInterface::CHANNEL_EMAIL, $data);

        $this->assertSame('foo@example.com', $logEntry->recipientEmail);
        $this->assertSame(['param' => 'test'], $logEntry->getParams());
        $this->assertSame('foo', $logEntry->subject);
        $this->assertSame('bar', $logEntry->bodyPlain);
        $this->assertSame('foobar', $logEntry->bodyHtml);
    }
}
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
            'params' => [
                'param' => 'test',
                'relatedObjectId' => 123,
                'relatedObjectType' => 'fooType',
                'toBeSentAt' => \Carbon\Carbon::create(2014, 10, 28, 14, 1, 30)
            ]
        ];

        $logEntry = \NotificationLogger::addNotification(NotificationInterface::CHANNEL_EMAIL, $data);

        $this->assertSame('foo@example.com', $logEntry->recipientEmail);
        $this->assertSame(['param' => 'test'], $logEntry->getParams());
        $this->assertSame('2014-10-28 14:01:30', $logEntry->toBeSentAt);
        $this->assertSame(123, $logEntry->relatedObjectId);
        $this->assertSame('fooType', $logEntry->relatedObjectType);
        $this->assertSame('foo', $logEntry->subject);
        $this->assertSame('bar', $logEntry->bodyPlain);
        $this->assertSame('foobar', $logEntry->bodyHtml);
    }
}
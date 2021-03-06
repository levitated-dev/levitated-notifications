<?php namespace Levitated\Notifications;

use \Mockery as m;
use \Carbon\Carbon;

class NotificationTest extends TestCase
{
    public function testSetChannelsAuto()
    {
        $renderer = $this->getMockRenderer();

        $n = new Notification(['emails' => ['foo@example.com']], 'bar', [], $renderer);
        $n->setChannelsAuto();
        $channels = $n->getChannels();
        $this->assertContains(Notification::CHANNEL_EMAIL, $channels);
        $this->assertCount(1, $channels);

        $n = new Notification(['phones' => ['123 123 123']], 'bar', [], $renderer);
        $n->setChannelsAuto();
        $channels = $n->getChannels();
        $this->assertContains(Notification::CHANNEL_SMS, $channels);
        $this->assertCount(1, $channels);

        $n = new Notification(['emails' => ['foo@example.com'], 'phones' => ['123 123 123']], 'bar', [], $renderer);
        $n->setChannelsAuto();
        $channels = $n->getChannels();
        $this->assertContains(Notification::CHANNEL_EMAIL, $channels);
        $this->assertContains(Notification::CHANNEL_SMS, $channels);
        $this->assertCount(2, $channels);
    }

    public function testSendEmail()
    {
        $renderer = $this->getMockRenderer();
        $sender = $this->getMockEmailSender();

        \Queue::shouldReceive('push')
            ->once()
            ->with(
                get_class($sender),
                m::contains('foo@example.com') // TODO: more precise check
            );

        $renderer->shouldReceive('render');
        $n = new Notification(['emails' => ['foo@example.com']], 'bar', [], $renderer, $sender);
        $n->send();
    }

    public function testSendSms()
    {
        $renderer = $this->getMockRenderer();
        $sender = $this->getMockSmsSender();

        \Queue::shouldReceive('push')
            ->once()
            ->with(
                get_class($sender),
                m::contains('123 123 123')
            );

        $renderer->shouldReceive('render');
        $n = new Notification(['phones' => ['123 123 123']], 'bar', [], $renderer, null, $sender);
        $n->send();
    }

    public function testSetRecipients()
    {
        $renderer = $this->getMockRenderer();

        $n = new Notification([], 'bar', [], $renderer);
        $n->setRecipients(['emails' => ['foo@example.com'], 'phones' => ['123 123 123', '456 456 456']]);
        $recipients = $n->getRecipients();
        $this->assertCount(2, $recipients);
        $this->assertArrayHasKey('emails', $recipients);
        $this->assertContains('foo@example.com', $recipients['emails']);
        $this->assertArrayHasKey('phones', $recipients);
        $this->assertContains('123 123 123', $recipients['phones']);
        $this->assertContains('456 456 456', $recipients['phones']);

        $n->setRecipients(['emails' => 'foo@example.com', 'phones' => ['']]);
        $recipients = $n->getRecipients();
        $this->assertCount(1, $recipients);
        $this->assertArrayHasKey('emails', $recipients);
        $this->assertContains('foo@example.com', $recipients['emails']);
    }

// TODO: move to separate test, update
//    public function testLogging()
//    {
//        $renderer = $this->getMockRenderer();
//        $sender = $this->getMockEmailSender();
//        \Config::set('notifications::logNotificationsInDb', true);
//
//        $logEntry = m::mock('\Levitated\Notifications\NotificationLogger');
//        $logEntry->shouldReceive('setAttribute');
//        $logEntry->shouldReceive('save');
//
//        \NotificationLogger::shouldReceive('addNotification')
//            ->times(3)
//            ->andReturn($logEntry);
//        $logEntry->id = 123;
//
//        // TODO: put jobId reference to another test
//        \Queue::shouldReceive('push')
//            ->times(3)
//            ->andReturn('exampleJobId');
//
//        $renderer->shouldReceive('render');
//        $n = new Notification(
//            [
//                'emails' => ['foo@example.com', 'bar@example.com'],
//                'phones' => ['123 123 123']
//            ],
//            'bar',
//            [],
//            $renderer,
//            $sender
//        );
//        $n->send();
//    }

    public function testSendSentAt()
    {
        $renderer = $this->getMockRenderer();
        $sender = $this->getMockSmsSender();

        $sendTime = Carbon::now()->addMinutes(15);
        \Queue::shouldReceive('later')
            ->once();

        $renderer->shouldReceive('render');
        $n = new Notification(['phones' => ['123 123 123']], 'bar', [], $renderer, null, $sender);
        $n->setSendTime($sendTime);
        $n->send();
    }
}
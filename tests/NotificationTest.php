<?php namespace Levitated\Notifications;

class NotificationTest extends TestCase {

    public function testSetChannelsAuto() {
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

    public function testSend() {
        // todo with laravel queue
        return;
        $queue = $this->getMockBuilder('Levitated\Notifications\NotificationQueueInterface')
            ->getMock();
        $queue->expects($this->once())
            ->method('queueEmail');
        $queue->expects($this->exactly(2))
            ->method('queueSms');

        $renderer = $this->getMockRenderer();
        $n = new Notification(['emails' => ['foo@example.com'], 'phones' => ['123 123 123', '456 456 456']], 'bar', [], $renderer);
        $n->send();
    }

    public function testSetRecipients() {
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
}
<?php namespace Levitated\Notifications;

class NotificationTest extends TestCase {

    public function testSetChannelsAuto() {
        $renderer = $this->getMockRenderer();

        $queue = $this->GetMockBuilder('Levitated\Notifications\NotificationQueueInterface')
            ->setMethods(['queueEmail', 'queueSms'])
            ->getMock();

        $n = new Notification(['emails' => ['foo@example.com']], 'bar', [], $renderer, $queue);
        $n->setChannelsAuto();
        $channels = $n->getChannels();
        $this->assertContains(Notification::CHANNEL_EMAIL, $channels);
        $this->assertCount(1, $channels);

        $n = new Notification(['phones' => ['123 123 123']], 'bar', [], $renderer, $queue);
        $n->setChannelsAuto();
        $channels = $n->getChannels();
        $this->assertContains(Notification::CHANNEL_SMS, $channels);
        $this->assertCount(1, $channels);

        $n = new Notification(['emails' => ['foo@example.com'], 'phones' => ['123 123 123']], 'bar', [], $renderer, $queue);
        $n->setChannelsAuto();
        $channels = $n->getChannels();
        $this->assertContains(Notification::CHANNEL_EMAIL, $channels);
        $this->assertContains(Notification::CHANNEL_SMS, $channels);
        $this->assertCount(2, $channels);
    }
}
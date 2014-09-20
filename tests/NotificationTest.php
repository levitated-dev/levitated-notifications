<?php namespace Levitated\Notifications;

use \Mockery as m;

class NotificationTest extends \Illuminate\Foundation\Testing\TestCase {
    /**
     * Creates the application.
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication() {
        // TODO: Implement createApplication() method.
        return require __DIR__.'/../../../../bootstrap/start.php';
    }



    //
//    protected function getPackageAliases()
//    {
//        return array(
//            'NotificationLogger' => 'Levitated\Notifications\Facades\NotificationLogger'
//        );
//    }
//
//    protected function getPackageProviders()
//    {
//        return array('Levitated\Notifications\NotificationsServiceProvider');
//    }

    protected function getMockRenderer() {
        return m::mock('Levitated\Notifications\NotificationRendererInterface');
    }

    protected function getMockEmailSender()
    {
        return m::mock('Levitated\Notifications\NotificationEmailSenderInterface');
    }

    protected function getMockSmsSender()
    {
        return m::mock('Levitated\Notifications\NotificationSmsSenderInterface');
    }

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

    public function testSendEmail() {
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

    public function testSendSms() {
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

    public function testLogging()
    {
        $renderer = $this->getMockRenderer();
        $sender = $this->getMockEmailSender();
        \Config::set('notifications::logNotificationsInDb', true);
/*
        \NotificationLogger::shouldReceive('addNotification')
            ->times(3);
*/
        $renderer->shouldReceive('render');
        $n = new Notification(
            [
                'emails' => ['foo@example.com', 'bar@example.com'],
                'phones' => ['123 123 123']
            ],
            'bar',
            [],
            $renderer,
            $sender
        );
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
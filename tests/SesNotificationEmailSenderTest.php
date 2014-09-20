<?php namespace Levitated\Notifications;

use Mockery as m;

class SesNotificationEmailSenderTest extends TestCase {

    protected function getJob() {
        return m::mock('\Illuminate\Queue\Jobs\RedisJob');
    }

    protected function setupSesMock() {
        $sesClient = m::mock('Aws\Ses\SesClient');
        \Aws\Laravel\AwsFacade::shouldReceive('get')->andReturn($sesClient);

        return $sesClient;
    }

    public function testFire() {
        $this->setupSesMock()->shouldReceive('sendEmail');
        $job = $this->getJob();
        $job->shouldReceive('delete');
        $sender = new SesNotificationEmailSender();
        $sender->fire(
            $job,
            [
                'renderedNotification' => [
                    'subject'   => 'foo',
                    'bodyPlain' => 'plain',
                    'bodyHtml'  => 'html'
                ],
                'recipientEmail'       => 'jan@levitated.pl',
                'params'               => []
            ]
        );
    }

    public function testSendingFailedOnce() {
        $this->setupSesMock()->shouldReceive('sendEmail')->andThrow(new \Exception());
        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(1);
        $job->shouldReceive('release');
        $sender = new SesNotificationEmailSender();
        $sender->fire(
            $job,
            [
                'renderedNotification' => [
                    'subject'   => 'foo',
                    'bodyPlain' => 'plain',
                    'bodyHtml'  => 'html'
                ],
                'recipientEmail'       => 'jan@levitated.pl',
                'params'               => []
            ]
        );
    }

    public function testSendingFailedTooManyTimes() {
        $this->setupSesMock()->shouldReceive('sendEmail')->andThrow(new \Exception());
        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(\Config::get('notifications::maxAttempts'));
        $job->shouldReceive('release');
        $sender = new SesNotificationEmailSender();

        try {
            $sender->fire(
                $job,
                [
                    'renderedNotification' => [
                        'subject'   => 'foo',
                        'bodyPlain' => 'plain',
                        'bodyHtml'  => 'html'
                    ],
                    'recipientEmail'       => 'jan@levitated.pl',
                    'params'               => []
                ]
            );
            $this->fail('Fire should throw an exception.');
        } catch (\Exception $e) {
        }
    }


    //
    //    public function testSend() {
    //        // todo with laravel queue
    //        return;
    //        $queue = $this->getMockBuilder('Levitated\Notifications\NotificationQueueInterface')
    //            ->getMock();
    //        $queue->expects($this->once())
    //            ->method('queueEmail');
    //        $queue->expects($this->exactly(2))
    //            ->method('queueSms');
    //
    //        $renderer = $this->getMockRenderer();
    //        $n = new Notification(['emails' => ['foo@example.com'], 'phones' => ['123 123 123', '456 456 456']], 'bar', [], $renderer);
    //        $n->send();
    //    }
    //
    //    public function testSetRecipients() {
    //        $renderer = $this->getMockRenderer();
    //
    //        $n = new Notification([], 'bar', [], $renderer);
    //        $n->setRecipients(['emails' => ['foo@example.com'], 'phones' => ['123 123 123', '456 456 456']]);
    //        $recipients = $n->getRecipients();
    //        $this->assertCount(2, $recipients);
    //        $this->assertArrayHasKey('emails', $recipients);
    //        $this->assertContains('foo@example.com', $recipients['emails']);
    //        $this->assertArrayHasKey('phones', $recipients);
    //        $this->assertContains('123 123 123', $recipients['phones']);
    //        $this->assertContains('456 456 456', $recipients['phones']);
    //
    //        $n->setRecipients(['emails' => 'foo@example.com', 'phones' => ['']]);
    //        $recipients = $n->getRecipients();
    //        $this->assertCount(1, $recipients);
    //        $this->assertArrayHasKey('emails', $recipients);
    //        $this->assertContains('foo@example.com', $recipients['emails']);
    //    }
}
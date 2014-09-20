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
}
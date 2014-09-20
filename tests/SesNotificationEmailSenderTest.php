<?php namespace Levitated\Notifications;

use Mockery as m;

class SesNotificationEmailSenderTest extends TestCase {
    protected function setupSesMock() {
        $sesClient = m::mock('Aws\Ses\SesClient');
        \Aws\Laravel\AwsFacade::shouldReceive('get')->andReturn($sesClient);

        return $sesClient;
    }

    public function testFire() {
        $this->setupSesMock()->shouldReceive('sendEmail');
        $job = $this->getJob();
        $job->shouldReceive('delete');
        $this->callSender($job);
    }

    public function testSendingFailedOnce() {
        $this->setupSesMock()->shouldReceive('sendEmail')->andThrow(new \Exception());
        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(1);
        $job->shouldReceive('release');
        $this->callSender($job);
    }

    public function testSendingFailedTooManyTimes() {
        $this->setupSesMock()->shouldReceive('sendEmail')->andThrow(new \Exception());
        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(\Config::get('notifications::maxAttempts'));
        $job->shouldReceive('release');

        try {
           $this->callSender($job);
            $this->fail('Fire should throw an exception.');
        } catch (\Exception $e) {
        }
    }

    protected function callSender($job) {
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

}
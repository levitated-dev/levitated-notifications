<?php namespace Levitated\Notifications;

use Mockery as m;
use \Aloha\Twilio\Facades\Twilio;

class TwilioNotificationSmsSenderTest extends TestCase {
    public function testFire() {
        Twilio::shouldReceive('message');
        $job = $this->getJob();
        $job->shouldReceive('delete');
        $this->callSender($job);
    }

    public function testSendingFailedOnce() {
        Twilio::shouldReceive('message');
        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(1);
        $job->shouldReceive('release');
        $this->callSender($job);
    }

    public function testSendingFailedTooManyTimes() {
        Twilio::shouldReceive('message');
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
        $sender = new TwilioNotificationSmsSender();
        $sender->fire(
            $job,
            [
                'renderedNotification' => [
                    'subject'   => 'foo',
                    'bodyPlain' => 'plain',
                ],
                'recipientPhone'       => '123 123 123',
                'params'               => []
            ]
        );
    }
}
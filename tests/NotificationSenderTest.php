<?php namespace Levitated\Notifications;

use Mockery as m;

class NotificationSenderTest extends TestCase {
    public function testHandleFailedJobRelease() {
        $stub = $this->getMockForAbstractClass('\Levitated\Notifications\NotificationSender');

        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(2);
        $job->shouldReceive('release')->with(2);
        $stub->handleFailedJob(new \Exception, $job, []);

        $job->shouldReceive('attempts')->andReturn(4);
        $job->shouldReceive('release')->with(10);
        $stub->handleFailedJob(new \Exception, $job, []);

        $job->shouldReceive('attempts')->andReturn(5);
        $job->shouldReceive('release')->with(10);
        $stub->handleFailedJob(new \Exception, $job, []);
    }

    /**
     * @expectedException \Exception
     */
    public function testHandleFailedJobFail() {
        $stub = $this->getMockForAbstractClass('\Levitated\Notifications\NotificationSender');

        $job = $this->getJob();
        $job->shouldReceive('attempts')->andReturn(6);
        $stub->handleFailedJob(new \Exception, $job, []);
    }
}
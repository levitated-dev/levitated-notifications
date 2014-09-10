<?php namespace Levitated\Notifications;

class TestCase extends \PHPUnit_Framework_TestCase {
    protected function getMockRenderer() {
        return $this->getMockBuilder('Levitated\Notifications\NotificationRendererInterface')
            ->getMock();
    }

    protected function getMockQueue() {
        return $this->GetMockBuilder('Levitated\Notifications\NotificationQueueInterface')
            ->getMock();
    }
}


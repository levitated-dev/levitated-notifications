<?php namespace Levitated\Notifications;

class TestCase extends \PHPUnit_Framework_TestCase {
    public function getMockRenderer() {
        return $this->getMockBuilder('Levitated\Notifications\NotificationRendererInterface')
            ->setMethods(['render'])
            ->getMock();
    }
}


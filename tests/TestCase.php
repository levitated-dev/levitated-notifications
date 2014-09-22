<?php namespace Levitated\Notifications;

use \Mockery as m;

class TestCase extends \Illuminate\Foundation\Testing\TestCase {
    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication() {
        $unitTesting = true;
        $testEnvironment = 'testing';

        return require __DIR__ . '/../../../../bootstrap/start.php';
    }

    public function tearDown() {
        m::close();
        $this->teardownDb();
    }

    public function setUp() {
        parent::setUp();
        $this->setUpDb();

        \Config::set('notifications::maxAttempts', 5);
        \Config::set('notifications::retryTimes', [1, 2, 5, 10]);
    }

    protected function setUpDb() {
        \Artisan::call('migrate');
        \Artisan::call('migrate', ['--bench' => 'levitated/notifications']);
    }

    public function teardownDb() {
        \Artisan::call('migrate:reset');
    }

    protected function getMockRenderer() {
        return m::mock('Levitated\Notifications\NotificationRendererInterface');
    }

    protected function getMockEmailSender() {
        return m::mock('Levitated\Notifications\NotificationEmailSenderInterface');
    }

    protected function getMockSmsSender() {
        return m::mock('Levitated\Notifications\NotificationSmsSenderInterface');
    }

    protected function getJob() {
        return m::mock('\Illuminate\Queue\Jobs\RedisJob');
    }
}


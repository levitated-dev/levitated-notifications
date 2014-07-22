<?php namespace Levitated\Notifications;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\Config;

class NotificationsDaemonCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'notifications:daemon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifications daemon.';

    protected $config;
    protected $daemonType;
    protected $iterationsPerSecond;

    public function fire() {
        $this->init();

        for ($i = 0; $i < $this->config['daemonIterations']; ++$i) {
            ob_start();
            try {

                switch ($this->daemonType) {
                    case 'email':
                        $this->emailSender();
                        break;

                    case 'sms':
                        $this->smsSender();
                        break;
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                usleep(1000000 * $this->config['daemonSleepAfterError']);
            }
            $output = ob_get_contents();
            ob_end_clean();

            if (!empty($output)) {
                $this->info('  ' . $output);
            }
            usleep(1000000 * (1 / $this->iterationsPerSecond));
        }
    }

    public function init() {
        $this->config = Config::get('notifications::config');
        $this->daemonType = $this->argument('daemonType');
        if (!in_array($this->daemonType, ['email', 'sms'])) {
            $this->error("Invalid daemon type {$this->daemonType}. Allowed ones are: email, sms.");

            return;
        }

        switch ($this->daemonType) {
            case 'email':
                $this->iterationsPerSecond = $maxIterationsPerSecond = $this->config['maxSentEmailsPerSecond'];
                break;

            case 'sms':
                $this->iterationsPerSecond = $maxIterationsPerSecond = $this->config['maxSentSmsesPerSecond'];
                break;
        }
    }

    public function emailSender() {
        NotificationQueue::sendEmails();
    }

    public function smsSender() {
        NotificationQueue::sendSmses();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments() {
        return [['daemonType', InputArgument::REQUIRED, 'email or sms']];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions() {
        return [];
    }
}

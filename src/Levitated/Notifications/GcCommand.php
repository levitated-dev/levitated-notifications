<?php

namespace Levitated\Notifications;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GcCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'notifications:gc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $deleted = NotificationLogger::gc();
        if ($deleted) {
            $this->info("Deleted {$deleted} old log entries.");
        } else {
            $this->info("Nothing to delete");
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}

<?php namespace Levitated\Notifications;

interface NotificationSmsSenderInterface {
    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param array                      $data
     */
    public function fire($job, $data);
}

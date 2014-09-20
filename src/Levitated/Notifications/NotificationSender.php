<?php namespace Levitated\Notifications;

use \Levitated\Helpers\Lh;

abstract class NotificationSender
{
    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param  array                     $data
     */
    abstract function fire($job, $data);

    /**
     * @param \Exception $e
     * @param \Illuminate\Queue\Jobs\Job                    $job
     * @param array|null                                    $data
     * @throws \Exception
     * @throws Exception
     */
    function handleFailedJob(\Exception $e, $job, $data = null) {
        $attempts = $job->attempts();
        if ($attempts < \Config::get('notifications::maxAttempts')) {
            // we didn't reach the maxAttempts time, release the job back to queue
            $retryTimes = \Config::get('notifications::retryTimes');
            $retryIn = LH::getVal($attempts - 1, $retryTimes, array_slice($retryTimes, -1)[0]);
            $job->release($retryIn);
        } else {
            // make the job fail
            throw $e;
        }
    }
}
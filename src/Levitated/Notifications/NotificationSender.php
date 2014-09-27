<?php namespace Levitated\Notifications;

use \Levitated\Helpers\LH;

abstract class NotificationSender {
    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param  array                     $data
     */
    abstract function fire($job, $data);

    /**
     * Get sender name (preferably class name without namespace to avoid log spamming)
     *
     * @return string
     */
    protected function getSenderName() {
        $className = get_called_class();
        $reflection = new \ReflectionClass($className);

        return $reflection->getShortName();
    }

    protected function getErrorLogData($data) {
        return [
            'logId' => LH::getVal('logId', $data)
        ];
    }

    /**
     * @param \Exception                 $e
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param array|null                 $data
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
            \Log::warning(static::getSenderName() . ': ' . $e->getMessage(), static::getErrorLogData($data));
        } else {
            // make the job fail
            \Log::error(static::getSenderName() . ': ' . $e->getMessage(), static::getErrorLogData($data));
            throw $e;
        }
    }
}
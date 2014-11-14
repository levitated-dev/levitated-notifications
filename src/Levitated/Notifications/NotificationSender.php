<?php namespace Levitated\Notifications;

use \Levitated\Helpers\LH;

abstract class NotificationSender
{
    const STATE_QUEUED = 'queued';
    const STATE_SENDING = 'sending';
    const STATE_SENT = 'sent';
    const STATE_FAILED = 'error';
    const STATE_DROPPED = 'dropped';

    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param  array $data
     */
    abstract function fire($job, $data);

    /**
     * Get sender name (preferably class name without namespace to avoid log spamming)
     *
     * @return string
     */
    protected function getSenderName()
    {
        $className = get_called_class();
        $reflection = new \ReflectionClass($className);

        return $reflection->getShortName();
    }

    protected function getErrorLogData($data)
    {
        return [
            'logId' => LH::getVal('logId', $data)
        ];
    }

    /**
     * @param \Exception $e
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param array|null $data
     * @throws \Exception
     * @throws Exception
     */
    public function handleFailedJob(\Exception $e, $job, $data = null)
    {
        $attempts = $job->attempts();
        if ($attempts < \Config::get('notifications::maxAttempts')) {
            // we didn't reach the maxAttempts time, release the job back to queue
            $retryTimes = \Config::get('notifications::retryTimes');
            $retryIn = LH::getVal($attempts - 1, $retryTimes, array_slice($retryTimes, -1)[0]);
            $job->release($retryIn);
            \Log::warning(static::getSenderName() . ': ' . $e->getMessage(), static::getErrorLogData($data));
            $this->setState($job, $data, self::STATE_QUEUED);
        } else {
            // make the job fail
            \Log::error(static::getSenderName() . ': ' . $e->getMessage(), static::getErrorLogData($data));
            $this->setState($job, $data, self::STATE_FAILED);
            throw $e;
        }
    }

    /**
     * Set job state in DB log. It works only if DB logging is on and a proper log exists.
     *
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param array $data
     * @param string $state
     */
    public function setState($job, $data, $state)
    {
        if (!\Config::get('notifications::logNotificationsInDb') || empty($data['logId'])) {
            return;
        }
        $logId = $data['logId'];

        try {
            $logEntry = \NotificationLogger::find($logId);
            $logEntry->state = $state;
            $logEntry->numAttempts = $job->attempts();
            if ($state = self::STATE_FAILED) {
                $logEntry->errorMessage = json_encode(static::getErrorLogData($data));
            }

            $logEntry->save();
        } catch (\Exception $e) {
            \Log::warning(static::getSenderName() . ': setState' . $e->getMessage(), static::getErrorLogData($data));
        }
    }

    public function getSimulateSending()
    {
        return (bool)\Config::get('notifications::simulateSending');
    }
}
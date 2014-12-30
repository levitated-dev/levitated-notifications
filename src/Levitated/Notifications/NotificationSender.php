<?php namespace Levitated\Notifications;

use \Levitated\Helpers\LH;

abstract class NotificationSender
{
    const STATE_QUEUED = 'queued';
    const STATE_SENDING = 'sending';
    const STATE_SENT = 'sent';
    const STATE_FAILED = 'error';
    const STATE_DROPPED = 'dropped';
    const STATE_DELETED = 'deleted';

    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param  array $data
     * @return bool if false the job should be not processed
     */
    public function fire($job, $data)
    {
        $logEntry = $this->getLogEntry($data);
        if ($logEntry && $logEntry->state == self::STATE_DELETED) {
            // job marked as deleted, simply skip it. More info in NotificationLogger::cancelNotification()
            $job->delete();
            return false;
        }
        return true;
    }

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
     * @throws Exception
     */
    public function setState($job, $data, $state)
    {
        try {
            $logEntry = $this->getLogEntry($data);
            if ($logEntry === null) {
                throw new \Exception('Log entry does not exist.');
            }
            $logEntry->state = $state;
            $logEntry->numAttempts = $job->attempts();
            if ($state == self::STATE_FAILED) {
                $logEntry->errorMessage = json_encode(static::getErrorLogData($data));
            }

            $logEntry->save();
        } catch (\Exception $e) {
            \Log::warning(static::getSenderName() . ': setState ' . $e->getMessage(), static::getErrorLogData($data));
        }
    }

    protected function getLogEntry($data)
    {
        if (!\Config::get('notifications::logNotificationsInDb') || empty($data['logId'])) {
            return null;
        }
        $logId = $data['logId'];
        $logEntry = \NotificationLogger::find($logId);
        return $logEntry;
    }

    public function getSimulateSending()
    {
        return (bool)\Config::get('notifications::simulateSending');
    }
}
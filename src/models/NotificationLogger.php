<?php namespace Levitated\Notifications;

use \Levitated\Helpers\LH;

class NotificationNotCancellableException extends \Exception
{
}

class NotificationLogger extends \Eloquent
{
    protected $table = 'notificationsLog';
    protected $fillable = ['channel', 'recipientEmail', 'recipientPhone', 'bodyPlain', 'bodyHtml', 'subject', 'params'];
    public $timestamps = false;

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $dbConnection = \Config::get('notifications::dbConnection');
        if ($dbConnection !== null) {
            $this->setConnection($dbConnection);
        }
    }

    protected function setAttributeAndUnsetParam($name, &$params)
    {
        if (isset($params[$name])) {
            $this->{$name} = $params[$name];
            unset($params[$name]);
        }
    }

    /**
     * @param string $channel
     * @param array $data
     * @return NotificationLogger
     */
    public static function addNotification($channel, $data)
    {
        $entry = new self;
        switch ($channel) {
            case NotificationInterface::CHANNEL_EMAIL:
                $entry->fillEmailData($data);
                break;

            case NotificationInterface::CHANNEL_SMS:
                $entry->fillSmsData($data);
                break;

            default:
                \Log::warning('Unsupported notification channel to log', array('channel' => $channel));
                // todo: throw, handle in client code
                return;
        }

        $entry->channel = $channel;
        $params = LH::getVal('params', $data, []);
        $entry->setAttributeAndUnsetParam('relatedObjectId', $params);
        $entry->setAttributeAndUnsetParam('relatedObjectType', $params);

        // if there's a send date set, convert it to string and remove from params to avoid redundancy
        if (!empty($params['toBeSentAt']) && $params['toBeSentAt'] instanceof \Carbon\Carbon) {
            $entry->toBeSentAt = $params['toBeSentAt']->copy()->format('Y-m-d H:i:s');
            unset($params['toBeSentAt']);
        }
        $entry->params = json_encode($params);
        if ($data['renderedNotification']) {
            $entry->fill($data['renderedNotification']);
        }
        $entry->save();
        return $entry;
    }

    public static function gc($params = [])
    {
        $date = new \DateTime;
        $date->modify(LH::getVal('removeOlderThan', $params, '-5 days'));
        $formatted_date = $date->format('Y-m-d H:i:s');
        return self::where('state', NotificationSender::STATE_SENT)->where('createdAt', '<=', $formatted_date)->delete();
    }

    public function getParams()
    {
        return (array)json_decode($this->params, true);
    }

    /**
     * @param array $data
     */
    protected function fillEmailData($data)
    {
        $this->recipientEmail = $data['recipientEmail'];
    }

    /**
     * @param array $data
     */
    protected function fillSmsData($data)
    {
        $this->recipientPhone = $data['recipientPhone'];
    }

    /**
     * Cancel a queued notification.
     *
     * @throws NotificationNotCancellableException
     */
    public function cancelNotification()
    {
        if (in_array($this->state, [NotificationSender::STATE_SENT, NotificationSender::STATE_SENDING])) {
            throw new NotificationNotCancellableException('This notification is either sent or is being sent.');
        }

        if (empty($this->jobId)) {
            // this will happen when using the sync queue driver
            throw new NotificationNotCancellableException('JobId not set.');
        }

        if (\Queue::getDefaultDriver() != 'redis') {
            throw new NotificationNotCancellableException('Job deletion is supported only in Redis queue.');
        }

        $redis = \Queue::getRedis();
        // WARNING: RedisQueue::getQueue is protected so for now I'm hardcoding the queue name. If the underlying implementation
        // change this should be caught by unit tests
        $queueName = 'queues:default:delayed';
        $redis->zrem($queueName, $this->jobId);

        $this->delete();
    }
}
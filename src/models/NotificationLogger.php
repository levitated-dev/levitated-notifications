<?php namespace Levitated\Notifications;

use \Levitated\Helpers\LH;

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
    public function addNotification($channel, $data)
    {
        switch ($channel) {
            case NotificationInterface::CHANNEL_EMAIL:
                $this->fillEmailData($data);
                break;

            case NotificationInterface::CHANNEL_SMS:
                $this->fillSmsData($data);
                break;

            default:
                \Log::warning('Unsupported notification channel to log', array('channel' => $channel));
                return;
        }

        $this->channel = $channel;
        $params = LH::getVal('params', $data, []);
        $this->setAttributeAndUnsetParam('relatedObjectId', $params);
        $this->setAttributeAndUnsetParam('relatedObjectType', $params);

        // if there's a send date set, convert it to string and remove from params to avoid redundancy
        if (!empty($params['toBeSentAt']) && $params['toBeSentAt'] instanceof \Carbon\Carbon) {
            $this->toBeSentAt = $params['toBeSentAt']->copy()->format('Y-m-d H:i:s');
            unset($params['toBeSentAt']);
        }
        $this->params = json_encode($params);
        if ($data['renderedNotification']) {
            $this->fill($data['renderedNotification']);
        }
        $this->save();
        return $this;
    }

    public static function gc($params = [])
    {
        $date = new \DateTime;
        $date->modify('-1 day');
        $formatted_date = $date->format('Y-m-d H:i:s');
        return self::where('state', NotificationSender::STATE_SENT)->where('createdAt', '<=', $formatted_date)->delete(
        );
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
}
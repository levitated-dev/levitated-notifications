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

    /**
     * @param string $channel
     * @param array $data
     * @return NotificationLogger
     */
    public function addNotification($channel, $data) {
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
        $this->params = json_encode(LH::getVal('params', $data, []));
        if ($data['renderedNotification']) {
            $this->fill($data['renderedNotification']);
        }
        $this->save();
        return $this;
    }

    public function getParams()
    {
        return (array)json_decode($this->params, true);
    }

    /**
     * @param array $data
     */
    protected function fillEmailData($data) {
        $this->recipientEmail = $data['recipientEmail'];
    }

    /**
     * @param array $data
     */
    protected function fillSmsData($data) {
        $this->recipientPhone = $data['recipientPhone'];
    }
}
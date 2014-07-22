<?php namespace Levitated\Notifications;

class Notification implements NotificationInterface
{
    protected $viewData;
    protected $channels = [];
    protected $recipients;

    protected $emailViewName;
    protected $relatedObjectType = 'unknown';
    protected $relatedObjectId;
    protected $toBeSentAt;

    protected $smsViewName;
    protected $data;

    public function __construct($recipients, $viewName, $viewData)
    {
        $this->setRecipients($recipients);
        $this->setViewData($viewData);
        $this->setViewName($viewName);
    }

    public function setChannelsAuto()
    {
        $recipients = $this->getRecipients();
        $channels = [];
        if (!empty($recipients['emails'])) {
            $channels[] = self::CHANNEL_EMAIL;
        }

        if (!empty($recipients['phones'])) {
            $channels[] = self::CHANNEL_SMS;
        }
        $this->setChannels($channels);
    }

    /**
     * @throws PhoneNumberNotSetException
     * @throws EmailNotSetException
     */
    public function send()
    {
        if ($this->getChannels() === []) {
            $this->setChannelsAuto();
        }

        $queue = new NotificationQueue();

        $viewName = $this->getViewName();
        $viewData = $this->getViewData();
        $recipients = $this->getRecipients();

        $params = [
            'relatedObjectType' => $this->relatedObjectType,
            'relatedObjectId' => $this->relatedObjectId,
            'toBeSentAt' => $this->toBeSentAt
        ];

        // send emails to queue
        if (in_array(self::CHANNEL_EMAIL, $this->getChannels())) {
            if (empty($recipients['emails'])) {
                throw new EmailNotSetException('No recipient phone provided for the SMS channel.');
            }

            if (!is_array($recipients['emails'])) {
                $recipients['emails'] = [$recipients['emails']];
            }

            foreach ($recipients['emails'] as $email) {
                $queue->queueEmail($email, $viewName, $viewData, $params);
            }
        }

        // send SMSes to queue
        if (in_array(self::CHANNEL_SMS, $this->getChannels())) {
            if (empty($recipients['phones'])) {
                throw new PhoneNumberNotSetException('No recipient phone provided for the SMS channel.');
            }

            if (!is_array($recipients['phones'])) {
                $recipients['phones'] = [$recipients['phones']];
            }

            foreach ($recipients['phones'] as $phone) {
                $queue->queueSms($phone, $viewName, $viewData, $params);
            }
        }
    }

    public function setSendTime($time)
    {
        $this->toBeSentAt = $time;
    }

    public function setRelatedObjectType($objectType)
    {
        $this->relatedObjectType = $objectType;
    }

    public function getRelatedObjectType()
    {
        return $this->relatedObjectType;
    }

    public function setRelatedObjectId($objectId)
    {
        $this->relatedObjectId = $objectId;
    }

    public function getRelatedObjectId()
    {
        return $this->relatedObjectId;
    }

    public function setViewData($viewData)
    {
        $this->viewData = $viewData;
    }

    public function getViewData()
    {
        return $this->viewData;
    }

    public function setViewName($emailViewName)
    {
        $this->emailViewName = $emailViewName;
    }

    public function getViewName()
    {
        return $this->emailViewName;
    }

    public function setSmsViewName($smsViewName)
    {
        $this->smsViewName = $smsViewName;
    }

    public function getSmsViewName()
    {
        return $this->smsViewName;
    }

    public function setRecipients($recipients)
    {
        $this->recipients = [];

        if (!empty($recipients['emails']) && !isObjectEmpty($recipients['emails'])) {
            if (is_string($recipients['emails'])) {
                $recipients['emails'] = explode(',', $recipients['emails']);
            }
            $this->recipients['emails'] = $recipients['emails'];
        }

        if (!empty($recipients['phones']) && !isObjectEmpty($recipients['phones'])) {
            if (is_string($recipients['phones'])) {
                $recipients['phones'] = explode(',', $recipients['phones']);
            }

            $this->recipients['phones'] = $recipients['phones'];
        }
    }

    /**
     * @return array
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    public function setChannels(array $channels)
    {
        $this->channels = $channels;
    }

    public function getChannels()
    {
        return $this->channels;
    }
}

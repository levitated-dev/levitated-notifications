<?php namespace Levitated\Notifications;

use Illuminate\Queue\QueueManager;
use Levitated\Helpers\LH;

/**
 * Class Notification
 *
 * @todo    implement sentAt
 * @package Levitated\Notifications
 */
class Notification implements NotificationInterface
{
    protected $renderer;
    protected $emailSender;
    protected $smsSender;

    protected $viewData;

    protected $channels = [];
    protected $recipients;
    protected $emailViewName;

    protected $smsViewName;
    protected $relatedObjectType = 'unknown';

    protected $relatedObjectId;
    protected $toBeSentAt;
    protected $data;

    public function __construct(
        $recipients,
        $viewName,
        $viewData,
        NotificationRendererInterface $renderer = null,
        NotificationEmailSenderInterface $emailSender = null,
        NotificationSmsSenderInterface $smsSender = null
    ) {
        $this->setRecipients($recipients);
        $this->setViewData($viewData);
        $this->setViewName($viewName);
        $this->renderer = LH::getVal(null, $renderer, \App::make('NotificationRenderer'));
        $this->emailSender = LH::getVal(null, $emailSender, \App::make('NotificationEmailSender'));
        $this->smsSender = LH::getVal(null, $smsSender, \App::make('NotificationSmsSender'));
    }

    /**
     * Automatically determine what channels is the notification using based on recipients list.
     */
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
     * Send the notification by inserting rendered emails and smses in the sending queue.
     *
     * @throws PhoneNumberNotSetException
     * @throws EmailNotSetException
     */
    public function send()
    {
        if ($this->getChannels() === []) {
            $this->setChannelsAuto();
        }

        $viewName = $this->getViewName();
        $viewData = $this->getViewData();
        $recipients = $this->getRecipients();

        $params = [
            'relatedObjectType' => $this->relatedObjectType,
            'relatedObjectId' => $this->relatedObjectId,
            'toBeSentAt' => $this->toBeSentAt
        ];

        $this->sendEmails($viewName, $viewData, $recipients, $params);
        $this->sendSmses($viewName, $viewData, $recipients, $params);
    }

    /**
     * @param string $viewName
     * @param array $viewData
     * @param array $recipients
     * @param array $params
     * @throws EmailNotSetException
     */
    protected function sendEmails($viewName, $viewData, $recipients, $params)
    {
        if (!in_array(self::CHANNEL_EMAIL, $this->getChannels())) {
            return;
        }
        if (empty($recipients['emails'])) {
            throw new EmailNotSetException('No email addresses provided.');
        }

        if (!is_array($recipients['emails'])) {
            $recipients['emails'] = [$recipients['emails']];
        }

        $renderedNotification = $this->renderer->render(
            NotificationRendererInterface::EMAIL,
            $viewName,
            $viewData,
            $params
        );

        foreach ($recipients['emails'] as $recipientEmail) {
            if ($this->sendOnlyToWhitelist()) {
                // skip emails not matching regexes on the white list
                foreach (\Config::get('notifications::emailsWhiteList') as $emailRegex) {
                    if (!preg_match($emailRegex, $recipientEmail)) {
                        continue;
                    }
                }
            }

            $data = [
                'recipientEmail' => $recipientEmail,
                'renderedNotification' => $renderedNotification,
                'params' => $params
            ];

            $this->putInQueue(self::CHANNEL_EMAIL, $data);
        }
    }


    /**
     * @param string $viewName
     * @param array $viewData
     * @param array $recipients
     * @param array $params
     * @throws PhoneNumberNotSetException
     */
    protected function sendSmses($viewName, $viewData, $recipients, $params)
    {
        if (!in_array(self::CHANNEL_SMS, $this->getChannels())) {
            return;
        }
        if (empty($recipients['phones'])) {
            throw new PhoneNumberNotSetException('No recipient phone provided for the SMS channel.');
        }

        if (!is_array($recipients['phones'])) {
            $recipients['phones'] = [$recipients['phones']];
        }

        $renderedNotification = $this->renderer->render(
            NotificationRendererInterface::SMS,
            $viewName,
            $viewData,
            $params
        );

        foreach ($recipients['phones'] as $recipientPhone) {
            if (self::sendOnlyToWhitelist()) {
                if (!in_array($recipientPhone, \Config::get('notifications::phoneNumberWhiteList'))) {
                    continue;
                }
            }

            $data = [
                'recipientPhone' => $recipientPhone,
                'renderedNotification' => $renderedNotification,
                'params' => $params
            ];

            $this->putInQueue(self::CHANNEL_SMS, $data);
        }
    }

    /**
     * @param string $channel
     * @return string
     */
    protected function getSenderClassName($channel)
    {
        switch ($channel) {
            case self::CHANNEL_SMS:
                $senderClass = $this->smsSender;
                break;

            case self::CHANNEL_EMAIL:
                $senderClass = $this->emailSender;
                break;
        }
        return get_class($senderClass);
    }

    /**
     * @param string $channel
     * @param array $data
     */
    protected function putInQueue($channel, $data)
    {
        // add DB log entry
        if (\Config::get('notifications::logNotificationsInDb')) {
            $data['logId'] = \NotificationLogger::addNotification($channel, $data);
        }

        $senderClass = $this->getSenderClassName($channel);
        if (empty($this->toBeSentAt)) {
            // send immediately
            $jobId = \Queue::push($senderClass, $data);
        } else {
            // send later
            $date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->toBeSentAt);
            $jobId = \Queue::later($date, $senderClass, $data);
        }
    }

    public function setSendTime(\Carbon\Carbon $time = null)
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

        if (!empty($recipients['emails']) && !LH::isObjectEmpty($recipients['emails'])) {
            if (is_string($recipients['emails'])) {
                $recipients['emails'] = explode(',', $recipients['emails']);
            }
            $this->recipients['emails'] = $recipients['emails'];
        }

        if (!empty($recipients['phones']) && !LH::isObjectEmpty($recipients['phones'])) {
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

    /**
     * Determine if notifications will be sent only to white-listed addresses.
     *
     * @return mixed
     */
    protected function sendOnlyToWhitelist()
    {
        return \Config::get('notifications::sendOnlyToWhitelist');
    }
}

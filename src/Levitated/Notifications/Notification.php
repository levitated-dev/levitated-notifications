<?php namespace Levitated\Notifications;

use Levitated\Helpers\LH;

class Notification implements NotificationInterface {
    protected $renderer;
    protected $queue;

    protected $viewData;
    protected $channels = [];
    protected $recipients;

    protected $emailViewName;
    protected $smsViewName;

    protected $relatedObjectType = 'unknown';
    protected $relatedObjectId;
    protected $toBeSentAt;

    protected $data;

    public function __construct($recipients, $viewName, $viewData, NotificationRendererInterface $renderer, NotificationQueueInterface $queue) {
        $this->setRecipients($recipients);
        $this->setViewData($viewData);
        $this->setViewName($viewName);
        $this->renderer = $renderer;
        $this->queue = $queue;
    }

    public function setChannelsAuto() {
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
    public function send() {
        if ($this->getChannels() === []) {
            $this->setChannelsAuto();
        }

        $viewName = $this->getViewName();
        $viewData = $this->getViewData();
        $recipients = $this->getRecipients();

        $params = [
            'relatedObjectType' => $this->relatedObjectType,
            'relatedObjectId'   => $this->relatedObjectId,
            'toBeSentAt'        => $this->toBeSentAt
        ];

        // send emails to queue
        if (in_array(self::CHANNEL_EMAIL, $this->getChannels())) {
            if (empty($recipients['emails'])) {
                throw new EmailNotSetException('No recipient phone provided for the SMS channel.');
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

            foreach ($recipients['emails'] as $email) {
                $this->queue->queueEmail($email, $renderedNotification, $params);
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

            $renderedNotification = $this->renderer->render(
                NotificationRendererInterface::SMS,
                $viewName,
                $viewData,
                $params
            );

            foreach ($recipients['phones'] as $phone) {
                $this->queue->queueSms($phone, $renderedNotification, $params);
            }
        }
    }

    public function setSendTime($time) {
        $this->toBeSentAt = $time;
    }

    public function setRelatedObjectType($objectType) {
        $this->relatedObjectType = $objectType;
    }

    public function getRelatedObjectType() {
        return $this->relatedObjectType;
    }

    public function setRelatedObjectId($objectId) {
        $this->relatedObjectId = $objectId;
    }

    public function getRelatedObjectId() {
        return $this->relatedObjectId;
    }

    public function setViewData($viewData) {
        $this->viewData = $viewData;
    }

    public function getViewData() {
        return $this->viewData;
    }

    public function setViewName($emailViewName) {
        $this->emailViewName = $emailViewName;
    }

    public function getViewName() {
        return $this->emailViewName;
    }

    public function setSmsViewName($smsViewName) {
        $this->smsViewName = $smsViewName;
    }

    public function getSmsViewName() {
        return $this->smsViewName;
    }

    public function setRecipients($recipients) {
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
    public function getRecipients() {
        return $this->recipients;
    }

    public function setChannels(array $channels) {
        $this->channels = $channels;
    }

    public function getChannels() {
        return $this->channels;
    }
}

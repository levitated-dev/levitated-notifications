<?php namespace Levitated\Notifications;

use Levitated\Helpers\LH;

class TwilioNotificationSmsSender extends NotificationSender implements NotificationSmsSenderInterface {
    public function fire($job, $data) {
        if (!parent::fire($job, $data)) {
            return;
        }

        $this->setState($job, $data, self::STATE_SENDING);
        try {
            $text = substr(trim($data['renderedNotification']['bodyPlain']), 0, 160);

            if (!$this->getSimulateSending()) {
                $twilio = new \Services_Twilio(\Config::get('notifications::twilioSid'), \Config::get('notifications::twilioToken'));
                $twilio->account->messages->sendMessage(
                    \Config::get('notifications::twilioFrom'),
                    $data['recipientPhone'],
                    $text
                );
            }
            $job->delete();
            $this->setState($job, $data, self::STATE_SENT);
            \Event::fire('Levitated\Notifications\Notification:smsSent', [$data]);
        } catch (\Exception $e) {
            $this->handleFailedJob($e, $job, $data);
        }
    }

    /**
     * Add recipient phone
     *
     * @param $data
     * @return array
     */
    protected function getErrorLogData($data) {
        $logData = parent::getErrorLogData($data);
        $logData['phone'] = LH::getVal('recipientPhone', $data);
        return $logData;
    }
}

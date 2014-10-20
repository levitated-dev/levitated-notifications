<?php namespace Levitated\Notifications;

use Levitated\Helpers\LH;

class TwilioNotificationSmsSender extends NotificationSender implements NotificationSmsSenderInterface {
    public function fire($job, $data) {
        try {
            $text = substr(trim($data['renderedNotification']['bodyPlain']), 0, 160);
            \Aloha\Twilio\Facades\Twilio::message(
                $data['recipientPhone'],
                $text
            );
            \Event::fire('Levitated\Notifications\Notification:smsSent', [$data]);
            $job->delete();
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

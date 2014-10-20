<?php namespace Levitated\Notifications;

use Levitated\Helpers\LH;

class TwilioNotificationSmsSender extends NotificationSender implements NotificationSmsSenderInterface {
    public function fire($job, $data) {
        try {
            \Aloha\Twilio\Facades\Twilio::message(
                $data['recipientPhone'],
                $data['renderedNotification']['bodyPlain']
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

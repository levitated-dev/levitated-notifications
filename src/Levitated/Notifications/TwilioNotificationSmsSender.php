<?php namespace Levitated\Notifications;

class TwilioNotificationSmsSender extends NotificationSender implements NotificationSmsSenderInterface {
    public function fire($job, $data) {
        try {
            \Aloha\Twilio\Facades\Twilio::message(
                $data['recipientPhone'],
                $data['renderedNotification']['bodyPlain']
            );
            $job->delete();
        } catch (\Exception $e) {
            $this->handleFailedJob($e, $job, $data);
        }
    }
}

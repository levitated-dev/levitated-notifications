<?php namespace Levitated\Notifications;

class TwilioNotificationSmsSender implements NotificationSmsSenderInterface {
    public function fire($job, $data) {
        try {
            \Aloha\Twilio\Facades\Twilio::message(
                $data['recipientPhone'],
                $data['renderedNotification']['bodyPlain']
            );
            $job->delete();
        } catch (\Exception $e) {
            if ($job->attempts() < \Config::get('notifications::maxAttempts')) {
                $job->release(15);
            } else {
                throw $e;
            }
        }
    }
}

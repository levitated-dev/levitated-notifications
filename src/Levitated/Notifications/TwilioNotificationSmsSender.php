<?php namespace Levitated\Notifications;

class TwilioNotificationSmsSender implements NotificationSmsSenderInterface {
    public function fire($job, $data) {
        $twilio = new \Services_Twilio(\Config::get('notifications::twilioSid'), \Config::get('notifications::twilioToken'));
        $result = $twilio->account->sms_messages->create(
            \Config::get('notifications::twilioFrom'),
            $data['recipientPhone'],
            $data['renderedNotification']['bodyPlain']
        );
        $job->delete();
    }
}

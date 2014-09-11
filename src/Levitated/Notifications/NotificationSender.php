<?php namespace Levitated\Notifications;

class NotificationSender {
    public function sendEmail($job, $data) {
        $recipientEmail = $data['recipientEmail'];
        $renderedNotification = $data['renderedNotification'];
        $params = $data['params'];

        if ($this->sendOnlyToWhitelist()) {
            // skip emails not matching regexes on the white list
            $emailMatches = false;
            foreach (\Config::get('notifications::emailsWhiteList') as $emailRegex) {
                if (preg_match($emailRegex, $recipientEmail)) {
                    $emailMatches = true;
                    break;
                }
            }
            if (!$emailMatches) {
                $job->delete();
                return;
            }
        }

        if (!\Config::get('notifications::simulateSending')) {
            $ses = \App::make('aws')->get('ses');
            $email = $this->getSESParams($recipientEmail, $renderedNotification, $params);
            $ses->sendEmail($email);
        }
        $job->delete();
    }

    protected function getSESParams($recipientEmail, $renderedNotification, $params) {
        $email = array(
            'Source'      => \Config::get('notifications::emailFrom'),
            'Destination' => array(
                'ToAddresses' => [$recipientEmail],
            ),
            'Message'     => array(
                'Subject' => array(
                    'Data'    => $renderedNotification['subject'],
                    'Charset' => 'utf8',
                ),
                'Body'    => array(
                    'Text' => array(
                        'Data'    => $renderedNotification['bodyPlain'],
                        'Charset' => 'utf8',
                    ),
                    'Html' => array(
                        'Data'    => $renderedNotification['bodyHtml'],
                        'Charset' => 'utf8',
                    ),
                ),
            ),
            'ReturnPath'  => \Config::get('notifications::emailReturnPath'),
        );

        if (!empty($params['replyTo'])) {
            $email['ReplyToAddresses'] = $params['replyTo'];
        }
        return $email;
    }

    public function sendSms($job, $data) {
        $recipientPhone = $data['recipientPhone'];

        if (self::sendOnlyToWhitelist()) {
            if (!in_array($recipientPhone, \Config::get('notifications::phoneNumberWhiteList'))) {
                $job->delete();
                return;
            }
        }

        if (!Config::get('notifications::simulateSending')) {
            \Twilio::message($recipientPhone, $data['renderedNotification']['bodyPlain']);
        }
        $job->delete();
    }

    public static function handleFailedJob($connection, $job, $data)
    {
        /*
        $retryIn = LH::getVal($job->attempts() - 1, $retryTimes, array_slice($retryTimes, -1)[0]);
        */
    }

    /**
     * Determine if notifications will be sent only to white-listed addresses.
     *
     * @return mixed
     */
    public static function sendOnlyToWhitelist() {
        return \Config::get('notifications::sendOnlyToWhitelist');
    }
}
<?php namespace Levitated\Notifications;

class SesNotificationEmailSender implements NotificationEmailSenderInterface {
    public function fire($job, $data) {
        $email = $this->getSESParams($data);
        $ses = \App::make('aws')->get('ses');
        $result = $ses->sendEmail($email);
        $job->delete();
    }

    protected function getSESParams($data) {
        $email = array(
            'Source'      => \Config::get('notifications::emailFrom'),
            'Destination' => array(
                'ToAddresses' => [$data['recipientEmail']],
            ),
            'Message'     => array(
                'Subject' => array(
                    'Data'    => $data['renderedNotification']['subject'],
                    'Charset' => 'utf8',
                ),
                'Body'    => array(
                    'Text' => array(
                        'Data'    => $data['renderedNotification']['bodyPlain'],
                        'Charset' => 'utf8',
                    ),
                    'Html' => array(
                        'Data'    => $data['renderedNotification']['bodyHtml'],
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
}

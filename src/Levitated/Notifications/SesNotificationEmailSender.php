<?php namespace Levitated\Notifications;

class SesNotificationEmailSender extends NotificationSender implements NotificationEmailSenderInterface {

    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param array                      $data
     * @throws \Exception
     */
    public function fire($job, $data) {
        $email = $this->getSESParams($data);
        $ses = \App::make('aws')->get('ses');
        try {
            $ses->sendEmail($email);
            $job->delete();
        } catch (\Exception $e) {
            $this->handleFailedJob($e, $job, $data);
        }
    }

    /**
     * @param $data
     * @return array
     */
    protected function getSESParams($data) {
        $email = [
            'Source'      => \Config::get('notifications::emailFrom'),
            'Destination' => [
                'ToAddresses' => [$data['recipientEmail']],
            ],
            'Message'     => [
                'Subject' => [
                    'Data'    => $data['renderedNotification']['subject'],
                    'Charset' => 'utf8',
                ],
                'Body'    => [
                    'Text' => [
                        'Data'    => $data['renderedNotification']['bodyPlain'],
                        'Charset' => 'utf8',
                    ],
                    'Html' => [
                        'Data'    => $data['renderedNotification']['bodyHtml'],
                        'Charset' => 'utf8',
                    ],
                ],
            ],
            'ReturnPath'  => \Config::get('notifications::emailReturnPath'),
        ];

        if (!empty($params['replyTo'])) {
            $email['ReplyToAddresses'] = $params['replyTo'];
        }

        return $email;
    }
}

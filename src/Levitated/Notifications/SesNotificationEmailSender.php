<?php namespace Levitated\Notifications;

use Levitated\Helpers\LH;

class SesNotificationEmailSender extends NotificationSender implements NotificationEmailSenderInterface {

    /**
     * @param \Illuminate\Queue\Jobs\Job $job
     * @param array $data
     * @return bool|void
     * @throws \Exception
     */
    public function fire($job, $data) {
        if (!parent::fire($job, $data)) {
            return;
        }

        $email = $this->getSESParams($data);
        $ses = \App::make('aws')->get('ses');
        $this->setState($job, $data, self::STATE_SENDING);
        try {
            if (!$this->getSimulateSending()) {
                $ses->sendEmail($email);
            }
            $job->delete();
            $this->setState($job, $data, self::STATE_SENT);
            \Event::fire('Levitated\Notifications\Notification:emailSent', [$data]);
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

    /**
     * Add recipient email and subject to the error log data.
     *
     * @param $data
     * @return array
     */
    protected function getErrorLogData($data) {
        $logData = parent::getErrorLogData($data);
        $logData['email'] = LH::getVal('recipientEmail', $data);
        $renderedNotification = LH::getVal('renderedNotification', $data);
        $logData['subject'] = LH::getVal('subject', $renderedNotification);
        return $logData;
    }
}

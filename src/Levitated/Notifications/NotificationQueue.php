<?php namespace Levitated\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NotificationQueue extends Model {
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';

    protected $table = 'notificationQueue';

    protected static function setNotificationParams($queuedNotification, $params) {
        $queuedNotification->relatedObjectType = getVal('relatedObjectType', $params);
        $queuedNotification->relatedObjectId = getVal('relatedObjectId', $params);
        $queuedNotification->toBeSentAt = getVal('toBeSentAt', $params);
    }

    /**
     * @param string $to
     * @param string $viewName
     * @param array  $data
     * @param array  $params
     * @throws MissingParamException
     */
    public static function queueEmail($to, $viewName = 'notifications/default.twig', $data, $params = []) {
        if (empty($to)) {
            throw new MissingParamException("Could not send Email - missing `to` field.");
        }

        $htmlTemplate = getVal($params, 'emailTemplate', 'notifications::notifications/layouts/html.twig');

        $queuedNotification = new self();
        $queuedNotification->type = self::TYPE_EMAIL;
        $queuedNotification->to = $to;
        $queuedNotification->fromName = Config::get('notifications::emailFrom');
        $queuedNotification->subject = \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/subject.twig'));
        $queuedNotification->bodyHtml = \Twig::render($viewName, $data + array('emailTemplate' => $htmlTemplate));
        $queuedNotification->bodyPlain = \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/plain.twig'));
        self::setNotificationParams($queuedNotification, $params);
        $queuedNotification->save();
    }

    /**
     * @param string $to
     * @param string $viewName
     * @param array  $data
     * @param array  $params
     * @throws MissingParamException
     */
    public static function queueSms($to, $viewName, $data, $params = []) {
        if (empty($to)) {
            throw new MissingParamException("Could not send SMS.");
        }

        $smsTemplate = \Twig::loadTemplate($viewName);
        $queuedNotification = new self();
        $queuedNotification->type = self::TYPE_SMS;
        $queuedNotification->to = $to;
        $queuedNotification->bodyPlain = $smsTemplate->renderBlock('sms', $data);
        self::setNotificationParams($queuedNotification, $params);
        $queuedNotification->save();
    }

    /**
     * @param int $amount
     * @throws \Exception
     */
    public static function sendEmails($amount = 1) {
        $queuedEmailsIds = DB::select("SELECT id FROM notificationQueue
                  WHERE state='queued'
                  AND type='" . self::TYPE_EMAIL . "'
                  AND (nextRetryAt IS NULL OR nextRetryAt < CURRENT_TIMESTAMP)
                  AND (toBeSentAt IS NULL OR toBeSentAt <= CURRENT_TIMESTAMP)
                  LIMIT ?", [$amount]);

        foreach ($queuedEmailsIds as $queuedEmailId) {
            $queuedEmail = self::find($queuedEmailId->id);
            echo '[' . date('Y-m-d H:i:s') . "] Sending email [ID {$queuedEmail->id}]'. {$queuedEmail->to} [{$queuedEmail->subject}]...";
            try {
                if (self::sendOnlyToWhitelist()) {
                    // skip emails not matching regexes on the white list
                    $emailMatches = false;
                    foreach (Config::get('notifications::emailsWhiteList') as $emailRegex) {
                        if (preg_match($emailRegex, $queuedEmail->to)) {
                            $emailMatches = true;
                            break;
                        }
                    }
                    if (!$emailMatches) {
                        $queuedEmail->state = 'skipped';
                        $queuedEmail->save();
                        echo "skipped";
                        continue;
                    }
                }

                if (!Config::get('notifications::simulateSending')) {
                    $ses = \App::make('aws')->get('ses');
                    $email = array(
                        'Source'      => $queuedEmail->fromName,
                        'Destination' => array(
                            'ToAddresses' => [$queuedEmail->to],
                        ),
                        'Message'     => array(
                            'Subject' => array(
                                'Data'    => $queuedEmail->subject,
                                'Charset' => 'utf8',
                            ),
                            'Body'    => array(
                                'Text' => array(
                                    'Data'    => $queuedEmail->bodyPlain,
                                    'Charset' => 'utf8',
                                ),
                                'Html' => array(
                                    'Data'    => $queuedEmail->bodyHtml,
                                    'Charset' => 'utf8',
                                ),
                            ),
                        ),
                        'ReturnPath'  => Config::get('notifications::emailReturnPath'),
                    );
                    if ($queuedEmail->replyTo) {
                        $email['ReplyToAddresses'] = $queuedEmail->replyTo;
                    }

                    $ses->sendEmail($email);
                }

                $queuedEmail->state = 'sent';
                $queuedEmail->sentAt = date(DB_DATE_FORMAT);
                $queuedEmail->save();
                self::generateEvents($queuedEmail);

                echo 'done' . PHP_EOL;
            } catch (\Exception $e) {
                if (isset($queuedEmail)) {
                    self::processFailedNotification($queuedEmail, $e);
                }
                throw $e;
            }
        }
    }

    public static function sendOnlyToWhitelist() {
        return Config::get('notifications::sendOnlyToWhitelist');
    }

    /**
     * @param int $amount
     * @throws \Exception
     */
    public static function sendSmses($amount = 1) {
        try {
            $queuedSmsesIds = DB::select("SELECT id FROM notificationQueue
                      WHERE state='queued'
                      AND type='" . self::TYPE_SMS . "'
                  AND (toBeSentAt IS NULL OR toBeSentAt <= CURRENT_TIMESTAMP)
                  AND (nextRetryAt IS NULL OR nextRetryAt < CURRENT_TIMESTAMP)
                  LIMIT ?", [$amount]);

            foreach ($queuedSmsesIds as $queuedSmsId) {
                $queuedSms = self::find($queuedSmsId->id);
                echo '[' . date('Y-m-d H:i:s') . "] Sending SMS [ID: {$queuedSms->id}]: {$queuedSms->to}...";
                if (self::sendOnlyToWhitelist()) {
                    if (!in_array($queuedSms->to, Config::get('notifications::phoneNumberWhiteList'))) {
                        echo 'skipped';
                        $queuedSms->state = 'skipped';
                        $queuedSms->save();
                        continue;
                    }
                }

                if (!Config::get('notifications::simulateSending')) {
                    \Twilio::message($queuedSms->to, $queuedSms->bodyPlain);
                }

                $queuedSms->state = 'sent';
                $queuedSms->sentAt = date(DB_DATE_FORMAT);
                $queuedSms->save();
                self::generateEvents($queuedSms);

                echo 'done' . PHP_EOL;
            }
        } catch (\Exception $e) {
            if (isset($queuedSms)) {
                self::processFailedNotification($queuedSms, $e);
            }
            throw $e;
        }
    }

    protected static function processFailedNotification(NotificationQueue $queuedNotification, \Exception $e) {
        $queuedNotification->tryNo++;
        echo "Failed (#{$queuedNotification->tryNo}): " . $e->getMessage() . PHP_EOL;
        $maxTries = Config::get('notifications::maxTriesNum');
        if ($queuedNotification->tryNo < $maxTries) {
            $retryTimes = Config::get('notifications::retryIn');
            $retryIn = getVal($queuedNotification->tryNo - 1, $retryTimes, array_slice($retryTimes, -1)[0]);
            $queuedNotification->nextRetryAt = date(DB_DATE_FORMAT, strtotime($retryIn));
            echo "Will retry in {$retryIn}" . PHP_EOL;
        } else {
            echo "Failed too many times, giving up." . PHP_EOL;
            $queuedNotification->state = 'failed';
        }
        $queuedNotification->save();
    }

    protected static function getTypeName($type) {
        $names = [self::TYPE_EMAIL => 'Email', self::TYPE_SMS => 'SMS'];

        return getVal($type, $names, 'unknown');
    }

    protected static function generateEvents($queuedNotification) {
        try {
            $relatedObjectType = $queuedNotification['relatedObjectType'];
            switch ($relatedObjectType) {
                case 'case':
                    $type = self::getTypeName($queuedNotification['type']);
                    $case = LessonCase::findOrFail($queuedNotification['relatedObjectId']);
                    $caseEvent = new LessonCaseEvent();
                    $caseEvent->lessonCaseId = $case->id;
                    $caseEvent->type = LessonCaseEvent::EMAIL_NOTIFICATION_SENT;
                    $caseEvent->data = $type . ' sent [' . $queuedNotification['to'] . ']: ' . $queuedNotification['bodyPlain'];
                    $caseEvent->save();
                    break;
            }
        } catch (\Exception $e) {
            echo 'sent, the following error happened after: ' . $e->getMessage() . PHP_EOL;
        }
    }
}

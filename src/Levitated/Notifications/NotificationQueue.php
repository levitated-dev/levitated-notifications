<?php namespace Levitated\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Levitated\Helpers\LH;

class NotificationQueue extends Model implements NotificationQueueInterface {
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';

    protected $table = 'notificationQueue';

    /**
     * Set optional parameters of the notification from an array.
     *
     * @param NotificationQueue $queuedNotification
     * @param array             $params
     */
    protected static function setNotificationParams(NotificationQueue $queuedNotification, $params) {
        $queuedNotification->relatedObjectType = LH::getVal('relatedObjectType', $params);
        $queuedNotification->relatedObjectId = LH::getVal('relatedObjectId', $params);
        $queuedNotification->toBeSentAt = LH::getVal('toBeSentAt', $params);
    }

    /**
     * Add an email to the queue.
     *
     * @param string $to
     * @param array  $renderedNotification
     * @param array  $params
     * @throws MissingParamException
     */
    public function queueEmail($to, $renderedNotification, $params = []) {
        if (empty($to)) {
            throw new MissingParamException("Could not send Email - missing `to` field.");
        }

        $queuedNotification = new self();
        $queuedNotification->type = self::TYPE_EMAIL;
        $queuedNotification->to = $to;
        $queuedNotification->fromName = Config::get('notifications::emailFrom');
        $queuedNotification->subject = $renderedNotification['subject'];
        $queuedNotification->bodyHtml = $renderedNotification['bodyHtml'];
        $queuedNotification->bodyPlain = $renderedNotification['bodyPlain'];
        self::setNotificationParams($queuedNotification, $params);
        $queuedNotification->save();
    }

    /**
     * Add a text message to the queue.
     *
     * @param string $to
     * @param array  $renderedNotification
     * @param array  $params
     * @throws MissingParamException
     */
    public function queueSms($to, $renderedNotification, $params = []) {
        if (empty($to)) {
            throw new MissingParamException("Could not send SMS.");
        }

        $queuedNotification = new self();
        $queuedNotification->type = self::TYPE_SMS;
        $queuedNotification->to = $to;
        $queuedNotification->bodyPlain = $renderedNotification['bodyPlain'];
        self::setNotificationParams($queuedNotification, $params);
        $queuedNotification->save();
    }

    /**
     * Send out emails.
     *
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

    /**
     * Determine if notifications will be sent only to white-listed addresses.
     *
     * @return mixed
     */
    public static function sendOnlyToWhitelist() {
        return Config::get('notifications::sendOnlyToWhitelist');
    }

    /**
     * Send out text messages.
     *
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

    /**
     * Process a notification that failed to send.
     *
     * @param NotificationQueue $queuedNotification
     * @param \Exception        $e
     */
    protected static function processFailedNotification(NotificationQueue $queuedNotification, \Exception $e) {
        $queuedNotification->tryNo++;
        echo "Failed (#{$queuedNotification->tryNo}): " . $e->getMessage() . PHP_EOL;
        $maxTries = Config::get('notifications::maxTriesNum');
        if ($queuedNotification->tryNo < $maxTries) {
            $retryTimes = Config::get('notifications::retryIn');
            $retryIn = LH::getVal($queuedNotification->tryNo - 1, $retryTimes, array_slice($retryTimes, -1)[0]);
            $queuedNotification->nextRetryAt = date(DB_DATE_FORMAT, strtotime($retryIn));
            echo "Will retry in {$retryIn}" . PHP_EOL;
        } else {
            echo "Failed too many times, giving up." . PHP_EOL;
            $queuedNotification->state = 'failed';
        }
        $queuedNotification->save();
    }

    /**
     * Get name of notification type.
     *
     * @param $type
     * @return mixed
     */
    protected static function getTypeName($type) {
        $names = [self::TYPE_EMAIL => 'Email', self::TYPE_SMS => 'SMS'];

        return LH::getVal($type, $names, 'unknown');
    }
}

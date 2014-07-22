<?php namespace Levitated\Notifications;

interface NotificationInterface
{
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';

    /**
     * @param array $recipients e.g. array('emails' => array('email1@example.com', 'email2@example.com'),
     *                          'phones' => array('123 456 789')).
     * @param string $viewName Name of view for this notification.
     * @param array  $viewData Data passed to the view.
     */
    public function __construct($recipients, $viewName, $viewData);

    /**
     * @return array
     */
    public function getRecipients();

    /**
     * Set channels (email, sms) through which this notification should be sent.
     *
     * @param array $channels
     * @return mixed
     */
    public function setChannels(array $channels);

    /**
     * Automatically set channels based on provided recipients.
     */
    public function setChannelsAuto();

    /**
     * @return array
     */
    public function getChannels();

    /**
     * Put notification into sending queue.
     */
    public function send();

    /**
     * Set type of related object.
     *
     * @param string $objectType
     */
    public function setRelatedObjectType($objectType);

    public function getRelatedObjectType();

    /**
     * Set id of related object.
     *
     * @param int $objectId
     */
    public function setRelatedObjectId($objectId);

    public function getRelatedObjectId();

    /**
     * Set when the notification should be sent, in server time zone.
     * If null passed (or method not called at all) it will be sent immediately.
     *
     * @param string|null $time e.g. '2014-07-03 16:30'
     */
    public function setSendTime($time);

    /**
     * @return string|null
     */
    public function getViewName();

    /**
     * @return array
     */
    public function getViewData();
}

class EmailNotSetException extends \Exception
{
}

class PhoneNumberNotSetException extends \Exception
{
}

<?php

return [
    'rendererClassName' => 'Levitated\Notifications\TwigNotificationRenderer',
    'emailSenderClassName' => 'Levitated\Notifications\SesNotificationEmailSender',
    'smsSenderClassName' => 'Levitated\Notifications\TwilioNotificationSmsSender',


    // Email config
    // fill with a verified SES sender
    'emailFrom' => 'Name <name@example.com>',

    // SMS config
    'twilioSid'=> '',
    'twilioToken' => '',
    'twilioFrom' => '',

    // number of sending tried before giving up.
    'maxAttempts' => 8,
    'retryTimes' => ['1', '2', '3', '10', '60'],

    'sendOnlyToWhitelist' => false,

    // if set to true the whole process of sending will work normally except nothing will be actually sent
    // do NOT set this to true in production unless you know what you're doing: everything will look normal
    // but no emails/smses will go out.
    'simulateSending' => false,

    // In debug mode SMSes will be sent ONLY to these phone numbers. Others will be skipped.
    'phoneNumberWhiteList' => [],

    // In debug mode emails will be sent ONLY to addresses matching the following *regexes*. Others will be skipped.
    'emailsWhiteList' => ['/@levitated\.pl|@example\.com/'],

    // If set to true all queued notifications will be also logged in mysql table by NotificationLog
    'logNotificationsInDb' => true,

    // notification logger implementation
    'dbNotificationModel' => 'Levitated\Notifications\NotificationLog',

    // connection name used
    'dbConnection' => null,
];

<?php

return [
    'rendererClassName' => 'Levitated\Notifications\TwigNotificationRenderer',
    'emailSenderClassName' => 'Levitated\Notifications\SesNotificationEmailSender',
    'smsSenderClassName' => 'Levitated\Notifications\TwilioNotificationEmailSender',

    // fill with a verified SES sender
    'emailFrom' => 'Name <name@example.com>',

    'twilioSid' => '[put sid here]',
    'twilioToken' => '[put token here]',
    'twilioFrom' => '[put twilio phone here]',

    // number of sending tried before giving up.
    'maxAttempts' => 8,
    'retryIn' => ['1', '2', '3', '10', '60'],

    'sendOnlyToWhitelist' => false,

    // if set to true the whole process of sending will work normally except nothing will be actually sent
    // do NOT set this to true in production unless you know what you're doing: everything will look normal
    // but no emails/smses will go out.
    'simulateSending' => false,

    // In debug mode SMSes will be sent ONLY to these phone numbers. Others will be skipped.
    'phoneNumberWhiteList' => [],

    // In debug mode emails will be sent ONLY to addresses matching the following regexes. Others will be skipped.
    'emailsWhiteList' => ['/@levitated\.pl|@mycollegetimeline\.com/'],

    'daemonIterations' => 500,
    'maxSentEmailsPerSecond' => 1,
    'maxSentSmsesPerSecond' => 1,
    'daemonSleepAfterError' => 1,
];

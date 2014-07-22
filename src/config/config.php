<?php

return [
    'emailFromName' => 'Notifications',

    // number of sending tried before giving up.
    'maxTriesNum' => 5,

    'retryIn' => ['+1 seconds', '+2 seconds', '+3 seconds'],

    'sendOnlyToWhitelist' => true,

    // if set to true the whole process of sending will work normally except nothing will be actually sent
    // do NOT set this to true in production unless you know what you're doing: everything will look normal
    // but no emails/smses will go out.
    'simulateSending' => true,

    // In debug mode SMSes will be sent ONLY to these phone numbers. Others will be skipped.
    'phoneNumberWhiteList' => [],

    // In debug mode emails will be sent ONLY to addresses matching the following regexes. Others will be skipped.
    'emailsWhiteList' => ['/@levitated\.pl/'],

    'daemonIterations' => 500,
    'maxSentEmailsPerSecond' => 1,
    'daemonSleepAfterError' => 1,
];

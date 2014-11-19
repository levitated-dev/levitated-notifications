<?php namespace Levitated\Notifications;

use Levitated\Helpers\LH;

class TwigNotificationRenderer implements NotificationRendererInterface {

    public function render($type, $viewName, $data, $params = []) {
        switch ($type) {
            case self::EMAIL:
                return $this->renderEmail($viewName, $data, $params);

            case self::SMS:
                return $this->renderSms($viewName, $data);
        }
    }

    protected function renderEmail($viewName, $data, $params = []) {
        $subjectTemplate = LH::getVal($params, 'emailSubjectTemplate', 'notifications::notifications/layouts/subject.twig');
        $htmlTemplate = LH::getVal($params, 'emailHtmlTemplate', 'notifications::notifications/layouts/html.twig');
        $plainTemplate = LH::getVal($params, 'emailPlainTemplate', 'notifications::notifications/layouts/plain.twig');

        return [
            'subject' => \Twig::render($viewName, $data + array('emailTemplate' => $subjectTemplate)),
            'bodyHtml'  => \Twig::render($viewName, $data + array('emailTemplate' => $htmlTemplate)),
            'bodyPlain' => \Twig::render($viewName, $data + array('emailTemplate' => $plainTemplate))
        ];
    }

    protected function renderSms($viewName, $data, $params = [])
    {
        $plainTemplate = LH::getVal($params, 'smsPlainTemplate', 'notifications/layouts/sms.twig');
        return [
            'bodyPlain' => \Twig::render($viewName, $data + array('emailTemplate' => $plainTemplate))
        ];
    }
}

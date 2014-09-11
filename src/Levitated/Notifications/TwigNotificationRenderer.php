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
        $htmlTemplate = LH::getVal($params, 'emailTemplate', 'notifications::notifications/layouts/html.twig');

        return [
            'subject'   => \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/subject.twig')),
            'bodyHtml'  => \Twig::render($viewName, $data + array('emailTemplate' => $htmlTemplate)),
            'bodyPlain' => \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/plain.twig'))
        ];
    }

    protected function renderSms($viewName, $data) {
        return [
            'bodyPlain' => \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/plain.twig'))
        ];
    }
}

<?php namespace Levitated\Notifications;

class TwigNotificationRenderer implements NotificationRendererInterface {

    public function render($type, $viewName, $data, $params = []) {
        switch ($type) {
            case self::EMAIL:
                return $this->_renderEmail($viewName, $data, $params);

            case self::SMS:
                return $this->_renderSms($viewName, $data);
        }
    }

    protected function _renderEmail($viewName, $data, $params = []) {
        $htmlTemplate = \LH::getVal($params, 'emailTemplate', 'notifications::notifications/layouts/html.twig');

        return [
            'subject'   => \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/subject.twig')),
            'bodyHtml'  => \Twig::render($viewName, $data + array('emailTemplate' => $htmlTemplate)),
            'bodyPlain' => \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/plain.twig'))
        ];
    }

    protected function _renderSms($viewName, $data) {
        return [
            'bodyPlain' => \Twig::render($viewName, $data + array('emailTemplate' => 'notifications::notifications/layouts/plain.twig'))
        ];
    }
}

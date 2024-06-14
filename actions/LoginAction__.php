<?php

namespace YesWiki\YunoHost;

use YesWiki\Core\YesWikiAction;
use YesWiki\Core\Controller\AuthController;

class LoginAction__ extends YesWikiAction
{
    public function run()
    {
        if ($this->params->has('enable_yunohost_sso') &&
            $this->params->has('yunohost_sso_domain') &&
            $this->params->get('enable_yunohost_sso')) {
            $incomingUrl = (empty($_SERVER['HTTPS']) ? 'http' : 'https').
                "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $encodedUrl = base64_encode($incomingUrl);
            $authController = $this->getService(AuthController::class);
            if ($authController->getLoggedUser()) {
                $this->output = preg_replace(
                    '~"https?://.*action=logout"~Ui',
                    'https://'.$this->params->get('yunohost_sso_domain').'/yunohost/sso/?action=logout&r='.$encodedUrl,
                    $this->output
                );
            } else {
                $this->output = '<a href="https://'.$this->params->get('yunohost_sso_domain').'/yunohost/sso/?r='.$encodedUrl.'" class="btn btn-default btn->
            <i class="glyphicon glyphicon-user"></i> '._t('LOGIN_LOGIN').'</a>';
            }
        }
    }
}

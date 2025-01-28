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
            if (!file_exists('files/yunohost_version')) {
                exec('dpkg-query --show --showformat=\'${Version}\' yunohost > files/yunohost_version');
            }
            $yunohostVersion = file_get_contents('files/yunohost_version');
            $majorYunohostVersion = explode('.', $yunohostVersion)[0] ?? '';
            // we default on current stable Yunohost's version urls
            $loginUrl = 'https://'.$this->params->get('yunohost_sso_domain').'/yunohost/sso/login?r='.$encodedUrl;
            $logoutUrl = 'https://'.$this->params->get('yunohost_sso_domain').'/yunohost/portalapi/logout?referer_redirect';
;
            if ($majorYunohostVersion == '11') { 
                // backward compatibility
                $loginUrl = 'https://'.$this->params->get('yunohost_sso_domain').'/yunohost/sso/?r='.$encodedUrl;
                $logoutUrl = 'https://'.$this->params->get('yunohost_sso_domain').'/yunohost/sso/?action=logout&r='.$encodedUrl;
            }

            $authController = $this->getService(AuthController::class);
            if ($authController->getLoggedUser()) {
                $this->output = preg_replace(
                    '~"https?://.*action=logout"~Ui',
                    $logoutUrl,
                    $this->output
                );
            } else {
                $this->output = '<a href="''.$loginUrl.'" class="btn btn-default btn->
            <i class="glyphicon glyphicon-user"></i> '._t('LOGIN_LOGIN').'</a>';
            }
        }
    }
}

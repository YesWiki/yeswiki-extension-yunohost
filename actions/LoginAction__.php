<?php

namespace YesWiki\YunoHost;

use YesWiki\Core\YesWikiAction;
use YesWiki\Core\Controller\AuthController;

class LoginAction__ extends YesWikiAction
{
    public function run()
    {
        $authController = $this->getService(AuthController::class);
        if ($authController->getLoggedUser()) {
            $this->output = preg_replace(
                '~"https?://.*action=logout"~Ui',
                '/yunohost/sso/?action=logout',
                $this->output
            );
        } else {
            $this->output = '<a href="/yunohost/sso/" class="btn btn-default btn-default ">
        <i class="glyphicon glyphicon-user"></i> Se connecter</a>';
        }
    }
}

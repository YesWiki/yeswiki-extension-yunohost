<?php

namespace YesWiki\YunoHost;

use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiAction;

class __LoginAction extends YesWikiAction
{
    public function run()
    {
        $authController = $this->services->get(AuthController::class);
        $userManager = $this->services->get(UserManager::class);

        if (empty($_SERVER['REMOTE_USER'])) {
            // disconnect any connected user
            $authController->logout();
        } else {
            $needLogin = true;
            if ($user = $authController->getLoggedUser()) {
                if ($user['name'] == $_SERVER['REMOTE_USER']) {
                    // good user is already connected, nothing to do
                    $needLogin = false;
                } else {
                    // we logout already logged user that doesn't match
                    $authController->logout();
                }
            }
            if ($needLogin) {
                $user = $userManager->getOneByName($_SERVER['REMOTE_USER']);
                if (!$user) {
                    // user needs to be created
                    $output = null;
                    $retval = null;
                    exec('sudo /usr/local/bin/yunohost-user-info.sh ' . escapeshellcmd($_SERVER['REMOTE_USER']) . ' --output-as json', $output, $retval);

                    if ($retval == 0) {
                        $email = json_decode($output[0])->mail;
                    } else {
                        exit('yunohost user info returned an error!');
                    }

                    // add to local database
                    $userManager->create($_SERVER['REMOTE_USER'], $email, 'Password handled by YunoHost SSO');
                    // get the user's info for login
                    $user = $userManager->getOneByName($_SERVER['REMOTE_USER']);
                }

                // login user
                if ($user) {
                    $authController->login($user);
                }
            }
        }
    }
}

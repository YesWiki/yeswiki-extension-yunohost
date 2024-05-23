<?php

use YesWiki\Core\Controller\AuthController;
use YesWiki\Core\Service\UserManager;

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

            // TODO how to get the real email ? yunohost:cli ?
            $email = $_SERVER['REMOTE_USER'].'@yunohost.local';

            // add to local database
            $userManager->create($_SERVER['REMOTE_USER'], $email, 'Password handled by YunoHost SSO');
            // get the user's info for login
            $user = $userManager->getOneByName($_SERVER['REMOTE_USER']);
        }

        // login user
        $authController->login($user);
    }
}

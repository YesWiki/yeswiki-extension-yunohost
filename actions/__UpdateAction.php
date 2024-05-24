<?php

namespace YesWiki\YunoHost;

use YesWiki\Core\YesWikiAction;

class __UpdateAction extends YesWikiAction
{
    public function run()
    {
        if (!empty($_GET['upgrade']) && $_GET['upgrade'] == 'yeswiki') {
            unset($_GET['upgrade']);
            return '<div class="alert alert-danger">YesWiki core update must be performed from Yunohost admin.</div>';
        }
    }
}

<?php

namespace YesWiki\Yunohost;

use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\AssetsManager;

class __ShowHandler extends YesWikiHandler
{
    public function run()
    {
        $this->wiki->services->get(AssetsManager::class)->AddJavascriptFile(
            'tools/yunohost/javascripts/yunohost-user.js',
            false,
            true
        );
    }
}

<?php

namespace YesWiki\Yunohost\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\ConsoleService;
use YesWiki\Wiki;

class YunoHostCliCommand extends Command
{
    protected $consoleService;
    protected $params;
    protected $wiki;

    public function __construct(Wiki &$wiki)
    {
        parent::__construct();
        $this->consoleService = $wiki->services->get(ConsoleService::class);
        $this->params = $wiki->services->get(ParameterBagInterface::class);
        $this->wiki = $wiki;
    }

    protected function configure()
    {
        $this
            ->setName('yunohost:cli')
            // the short description shown while running "./yeswicli list"
            ->setDescription('YunoHost CLI wrapper for YesWiki.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Use YunoHost CLI from your YesWiki instance.' . "\n" .
                "It handles SSH or local YunoHost installations\n");
    }
    protected function checkConfig(OutputInterface $output)
    {
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->checkConfig($output)) {
            return Command::INVALID;
        }
    }
}

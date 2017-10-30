<?php

namespace League\ThreadPool\Service\Commands;

use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Interfaces\DaemonInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class NonDaemonCommand extends Command
{
    /**
     * @var DaemonInterface
     */
    private $daemon;

    /**
     * StartCommand constructor.
     *
     * @param null $name
     * @param DaemonInterface $daemon
     *
     * @throws LogicException
     */
    public function __construct($name = null, DaemonInterface $daemon)
    {
        parent::__construct($name);

        $this->daemon = $daemon;
    }

    protected function configure(): void
    {
        $this
            ->setName('non-daemon')
            ->setDescription('Starts daemon server without demonization')
            ->setHelp('This command try to start daemon server without demonization');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->daemon->start(true);
        } catch (DaemonException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
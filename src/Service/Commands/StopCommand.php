<?php

namespace League\ThreadPool\Service\Commands;

use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Interfaces\DaemonInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StopCommand extends Command
{
    /**
     * @var DaemonInterface
     */
    private $daemon;

    /**
     * StopCommand constructor.
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
            ->setName('stop')
            ->setDescription('Stops daemon server')
            ->setHelp('This command try to stop daemon server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $isStopped = $this->daemon->stop();

            if ($isStopped) {
                $output->writeln('<comment>Daemons successfully stopped</comment>');
            } else {
                $output->writeln("<error>Can't stop daemon</error>");
            }
        } catch (DaemonException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
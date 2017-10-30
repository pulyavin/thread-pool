<?php

namespace League\ThreadPool\Service\Commands;

use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Interfaces\DaemonInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StartCommand extends Command
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
            ->setName('start')
            ->setDescription('Starts daemon server')
            ->setHelp('This command try to start daemon server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $isStarted = $this->daemon->start();

            if ($isStarted) {
                usleep(500000);

                $pid = $this->daemon->status();

                $output->writeln("<comment>Daemons successfully started on PID {$pid}</comment>");
            } else {
                $output->writeln("<error>Can\'t start daemon</error>");
            }
        } catch (DaemonException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
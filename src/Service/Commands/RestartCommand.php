<?php

namespace League\ThreadPool\Service\Commands;

use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Interfaces\DaemonInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RestartCommand extends Command
{
    /**
     * @var DaemonInterface
     */
    private $daemon;

    /**
     * RestartCommand constructor.
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
            ->setName('restart')
            ->setDescription('Restarts daemon server')
            ->setHelp('This command try to stop and start daemon server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pid = $this->daemon->getPid();

        if ($pid !== 0) {
            try {
                if ($this->daemon->stop() === true) {
                    $output->writeln('<comment>Daemon successfully stopped</comment>');
                }

                for ($i = 1; $i <= 5; $i++) {
                    $output->writeln("<comment>Waiting {$i} sec...</comment>");
                }

                $output->writeln('<comment>Trying to start daemon</comment>');

                if ($this->daemon->start() === true) {
                    $output->writeln("<comment>Daemons successfully started on PID {$this->daemon->getPid()}</comment>");
                } else {
                    $output->writeln('<error>Error with starting daemon</error>');
                }
            } catch (DaemonException $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }
    }
}
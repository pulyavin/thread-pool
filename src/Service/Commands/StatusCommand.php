<?php

namespace League\ThreadPool\Service\Commands;

use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Interfaces\DaemonInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StatusCommand extends Command
{
    /**
     * @var DaemonInterface
     */
    private $daemon;

    /**
     * StatusCommand constructor.
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
            ->setName('status')
            ->setDescription('Shows daemon server status')
            ->setHelp('This command returns information about current daemon server status')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $pid = $this->daemon->status();

            if ($pid !== 0) {
                $output->writeln("<info>Works on pid {$pid}</info>");
            } else {
                $output->writeln('<info>Daemons stopped</info>');
            }
        } catch (DaemonException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
<?php

namespace League\ThreadPool\Service;

use League\ThreadPool\Interfaces\DaemonInterface;
use League\ThreadPool\Service\Commands\NonDaemonCommand;
use League\ThreadPool\Service\Commands\RestartCommand;
use League\ThreadPool\Service\Commands\StartCommand;
use League\ThreadPool\Service\Commands\StatusCommand;
use League\ThreadPool\Service\Commands\StopCommand;
use Symfony\Component\Console\Application;


class Service
{
    /**
     * @var Application
     */
    private $application;

    /**
     * Service constructor.
     *
     * @param DaemonInterface $daemon
     */
    public function __construct(DaemonInterface $daemon)
    {
        $this->application = new Application();

        $this->application->add(new StartCommand('start', $daemon));
        $this->application->add(new StopCommand('stop', $daemon));
        $this->application->add(new RestartCommand('restart', $daemon));
        $this->application->add(new StatusCommand('status', $daemon));
        $this->application->add(new NonDaemonCommand('non-daemon', $daemon));
    }

    public function run(): void
    {
        $this->application->run();
    }
}
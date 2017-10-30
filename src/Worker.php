<?php

namespace League\ThreadPool;

use League\ThreadPool\Exceptions\Thread\AlarmException;
use League\ThreadPool\Exceptions\Thread\HangupException;
use League\ThreadPool\Exceptions\Thread\InterruptedException;
use League\ThreadPool\Exceptions\Thread\TerminationException;
use League\ThreadPool\Interfaces\RunnableInterface;
use League\ThreadPool\Interfaces\SystemCallsInterface;


class Worker
{
    /**
     * @var null|string
     */
    private $name;

    /**
     * @var array
     */
    private $shutdownFunctions;

    /**
     * @var RunnableInterface
     */
    private $runnable;

    /**
     * @var SystemCallsInterface
     */
    private $systemCalls;

    /**
     * Worker constructor.
     *
     * @param null|string $name
     * @param array $shutdownFunctions
     * @param RunnableInterface $runnable
     * @param SystemCallsInterface $systemCalls
     */
    public function __construct(
        ?string $name,
        array $shutdownFunctions = [],
        RunnableInterface $runnable,
        SystemCallsInterface $systemCalls
    )
    {
        $this->name = $name;
        $this->shutdownFunctions = $shutdownFunctions;
        $this->runnable = $runnable;
        $this->systemCalls = $systemCalls;
    }

    public function run(): void
    {
        if ($this->name) {
            $this->systemCalls->setProcessTitle($this->name);
        }

        $this->registerSignals();

        try {
            $exitCode = $this->runnable->run();
        } catch (\Exception $e) {
            $exitCode = $e->getCode() ?: ExitConstants::EXIT_EXCEPTION_IN_THREAD;
        }

        $this->callShutdownFunctions();

        $this->systemCalls->quit($exitCode);
    }

    protected function registerSignals(): void
    {
        $this->systemCalls->listenSignal(SIGTERM, function () {
            throw new TerminationException;
        });

        $this->systemCalls->listenSignal(SIGINT, function () {
            throw new InterruptedException;
        });

        $this->systemCalls->listenSignal(SIGALRM, function () {
            throw new AlarmException;
        });

        $this->systemCalls->listenSignal(SIGHUP, function () {
            throw new HangupException;
        });
    }

    /**
     * Calls shutdown functions when thread closed
     */
    protected function callShutdownFunctions(): void
    {
        foreach ($this->shutdownFunctions as $function) {
            try {
                $function();
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
<?php

namespace League\ThreadPool;

use League\ThreadPool\Exceptions\Thread\ActiveThreadException;
use League\ThreadPool\Exceptions\Thread\CouldNotForkException;
use League\ThreadPool\Exceptions\Thread\DeadProcessException;
use League\ThreadPool\Exceptions\Thread\WasLaunchedException;
use League\ThreadPool\Interfaces\RunnableInterface;
use League\ThreadPool\Interfaces\SystemCallsInterface;
use League\ThreadPool\Interfaces\ThreadInterface;


class Thread implements ThreadInterface
{
    /**
     * @var bool
     */
    protected $isLaunched = false;

    /**
     * @var RunnableInterface
     */
    protected $runnable;

    /**
     * Title of current process
     *
     * @var string
     */
    protected $name;

    /**
     * Pid of current process
     *
     * @var integer
     */
    protected $pid;

    /**
     * List of shutdown functions
     *
     * @var array
     */
    protected $shutdownFunctions = [];

    /**
     * @var int
     */
    protected $exitCode;

    /**
     * Wrapper of system calls functions
     *
     * @var SystemCallsInterface
     */
    protected $systemCalls;

    /**
     * Thread constructor.
     *
     * @param RunnableInterface $runnable
     * @param SystemCallsInterface $systemCalls
     */
    public function __construct(
        RunnableInterface $runnable,
        SystemCallsInterface $systemCalls = null
    )
    {
        if ($systemCalls === null) {
            $systemCalls = new SystemCalls;
        }

        $this->runnable = $runnable;
        $this->systemCalls = $systemCalls;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name): ThreadInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerShutdownFunction(callable $callable): ThreadInterface
    {
        $this->shutdownFunctions[] = $callable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function start($isSynchronous = false): bool
    {
        if ($this->isLaunched) {
            throw new WasLaunchedException;
        }

        $this->isLaunched = true;

        if ($isSynchronous === true) {
            try {
                $exitCode = $this->runnable->run();
            } catch (\Exception $e) {
                $exitCode = $e->getCode() ?: ExitConstants::EXIT_EXCEPTION_IN_THREAD;
            }

            $this->exitCode = $exitCode;

            return true;
        }

        $pid = $this->systemCalls->fork();

        if ($pid === -1) {
            throw new CouldNotForkException;
        }

        // this is parent process
        if ($pid) {
            $this->pid = $pid;

            return true;
        }

        $worker = new Worker(
            $this->name,
            $this->shutdownFunctions,
            $this->runnable,
            $this->systemCalls
        );
        // runs in new process
        $worker->run();

        // never because exit will call
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): bool
    {
        if ($this->isLaunched === false || $this->pid === null) {
            return false;
        }

        $isActive = $this->waitingCheck();

        if ($isActive === false) {
            return true;
        }

        return $this->systemCalls->terminateProcess($this->pid);
    }

    /**
     * {@inheritdoc}
     */
    public function read($isSynchronous = false): int
    {
        if ($this->exitCode !== null) {
            return $this->exitCode;
        }

        $isActive = $this->waitingCheck($isSynchronous);

        if ($isActive === false) {
            if ($this->exitCode === null) {
                throw new DeadProcessException("Can't read exit code form dead process with PID \"{$this->pid}\"");
            }

            return $this->exitCode;
        }

        throw new ActiveThreadException;
    }

    /**
     * {@inheritdoc}
     */
    public function join($isSynchronous = false): bool
    {
        if ($isSynchronous === true) {
            $pid = $this->systemCalls->synchronousWaiting($this->pid, $status);
        } else {
            $pid = $this->systemCalls->aSynchronousWaiting($this->pid, $status);
        }

        if ($pid === 0) {
            return false;
        }

        $this->exitCode = $this->systemCalls->getExitCode($status);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return $this->waitingCheck();
    }

    /**
     * Checks if this process is active
     *
     * @param bool $isSynchronous
     *
     * @return bool
     */
    protected function waitingCheck($isSynchronous = false): bool
    {
        $isAlive = $this->systemCalls->isProcessRunning($this->pid);

        if ($isAlive === false) {
            return false;
        }

        return !$this->join($isSynchronous);
    }
}
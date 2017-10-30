<?php

namespace League\ThreadPool\Interfaces;

use League\ThreadPool\Exceptions\Daemon\AlreadyStoppedException;
use League\ThreadPool\Exceptions\Daemon\CantStopDaemonException;
use League\ThreadPool\Exceptions\Daemon\CouldNotForkException;
use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Exceptions\Daemon\InvalidPidPathException;
use League\ThreadPool\Exceptions\Daemon\AlreadyRunningException;


interface DaemonInterface
{
    /**
     * @param bool $isSynchronous
     *
     * @return bool
     *
     * @throws CouldNotForkException
     * @throws AlreadyRunningException
     * @throws InvalidPidPathException
     * @throws DaemonException
     */
    public function start($isSynchronous = false): bool;

    /**
     * @return boolean
     *
     * @throws AlreadyStoppedException
     * @throws CantStopDaemonException
     */
    public function stop(): bool;

    /**
     * Returns PID of daemon process
     *
     * @return int
     */
    public function getPid(): int;
}
<?php

namespace League\ThreadPool\Interfaces;

use League\ThreadPool\Exceptions\Thread\ActiveThreadException;
use League\ThreadPool\Exceptions\Thread\CouldNotForkException;
use League\ThreadPool\Exceptions\Thread\DeadProcessException;
use League\ThreadPool\Exceptions\Thread\WasLaunchedException;


interface ThreadInterface
{
    /**
     * Starts this thread
     *
     * @param bool $isSynchronous
     *
     * @return bool
     *
     * @throws WasLaunchedException
     * @throws CouldNotForkException
     */
    public function start($isSynchronous = false): bool;

    /**
     * Interrupts this thread
     *
     * @return bool
     */
    public function stop(): bool;

    /**
     * Waits end of process. Returns success flag of waiting result
     *
     * @param bool $isSynchronous
     *
     * @return bool
     */
    public function join($isSynchronous = false): bool;

    /**
     * Reads exit code of stopped process
     *
     * @param bool $isSynchronous
     * 
     * @return int
     *
     * @throws ActiveThreadException
     * @throws DeadProcessException
     */
    public function read($isSynchronous = false): int;

    /**
     * Checks if this process is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Sets name to thread
     *
     * @param $name
     *
     * @return ThreadInterface
     */
    public function setName($name): ThreadInterface;

    /**
     * @param callable $callable
     *
     * @return ThreadInterface
     */
    public function registerShutdownFunction(callable $callable): ThreadInterface;
}

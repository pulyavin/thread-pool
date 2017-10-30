<?php

namespace League\ThreadPool\Interfaces;


interface SystemCallsInterface
{
    /**
     * Forks the currently running process
     *
     * @return int
     */
    public function fork(): int;

    /**
     * Calls signal handlers for pending signals
     *
     * @return bool
     */
    public function dispatchSignals(): bool;

    /**
     * Sets the process title
     *
     * @param string $title
     *
     * @return bool
     */
    public function setProcessTitle(string $title): bool ;

    /**
     * Installs a signal handler
     *
     * @param int $sig
     * @param callable|int $handler
     *
     * @return bool
     */
    public function listenSignal(int $sig, $handler): bool;

    /**
     * Executes default behavior for signal
     *
     * @param int $sig
     *
     * @return mixed
     */
    public function ignoreSignal(int $sig);

    /**
     * Returns the return code of a terminated child
     *
     * @param int $status
     *
     * @return int
     */
    public function getExitCode($status): int;

    /**
     * Synchronously Waits on or returns the status of a forked child
     *
     * @param int $pid
     * @param int $status
     *
     * @return int
     */
    public function synchronousWaiting(int $pid, &$status): int;

    /**
     * Asynchronously Waits on or returns the status of a forked child
     *
     * @param int $pid
     * @param int $status
     *
     * @return int
     */
    public function aSynchronousWaiting(int $pid, &$status): int;

    /**
     * Make the current process a session leader
     *
     * @return int
     */
    public function setSid(): int;

    /**
     * Terminates execution of the script. Shutdown functions and object destructors will always be executed even if exit is called.
     *
     * @param int $status
     *
     * @return void
     */
    public function quit(int $status): void;

    /**
     * Delay execution interval
     *
     * @return void
     */
    public function waitInterval(): void;

    /**
     * Terminate current process
     *
     * @param int $pid
     *
     * @return bool
     */
    public function terminateProcess(int $pid): bool;

    /**
     * Checks if process running now
     *
     * @param int $pid
     *
     * @return bool
     */
    public function isProcessRunning(int $pid): bool;
}
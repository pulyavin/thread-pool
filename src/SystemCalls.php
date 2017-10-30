<?php

namespace League\ThreadPool;

use League\ThreadPool\Interfaces\SystemCallsInterface;


class SystemCalls implements SystemCallsInterface
{
    /**
     * {@inheritdoc}
     */
    public function fork(): int
    {
        return pcntl_fork();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatchSignals(): bool
    {
        return pcntl_signal_dispatch();
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessTitle(string $title): bool
    {
        return cli_set_process_title($title);
    }

    /**
     * {@inheritdoc}
     */
    public function listenSignal(int $sig, $handler): bool
    {
        return pcntl_signal($sig, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function getExitCode($status): int
    {
        return pcntl_wexitstatus($status);
    }

    /**
     * {@inheritdoc}
     */
    public function setSid(): int
    {
        return posix_setsid();
    }

    /**
     * {@inheritdoc}
     */
    public function quit(int $status): void
    {
        exit($status);
    }

    /**
     * {@inheritdoc}
     */
    public function terminateProcess(int $pid): bool
    {
        return posix_kill($pid, SIGTERM);
    }

    /**
     * {@inheritdoc}
     */
    public function isProcessRunning(int $pid): bool
    {
        return posix_kill($pid, SIG_DFL);
    }

    /**
     * {@inheritdoc}
     */
    public function synchronousWaiting(int $pid, &$status): int
    {
        return pcntl_waitpid($pid, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function aSynchronousWaiting(int $pid, &$status): int
    {
        return pcntl_waitpid($pid, $status, WNOHANG);
    }

    /**
     * {@inheritdoc}
     */
    public function ignoreSignal(int $sig)
    {
        return pcntl_signal($sig, SIG_IGN);
    }

    /**
     * {@inheritdoc}
     */
    public function waitInterval(): void
    {
        usleep(100000);
    }
}
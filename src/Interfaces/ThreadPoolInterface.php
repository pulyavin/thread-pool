<?php

namespace League\ThreadPool\Interfaces;


interface ThreadPoolInterface
{
    /**
     * @param int $maxThreads
     */
    public function setMaxThreads(int $maxThreads = 0): void;

    /**
     * Submit new thread in thread pool
     *
     * @param ThreadInterface $thread
     * @param callable|null $callback
     * @param bool $waitIfFull
     *
     * @return bool
     */
    public function submit(ThreadInterface $thread, callable $callback = null, $waitIfFull = false): bool;

    /**
     * @param bool $isSynchronous
     *
     * @return bool
     */
    public function join($isSynchronous = true): bool;
}
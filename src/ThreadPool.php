<?php

namespace League\ThreadPool;

use League\ThreadPool\Exceptions\Thread\ActiveThreadException;
use League\ThreadPool\Exceptions\Thread\DeadProcessException;
use League\ThreadPool\Exceptions\Thread\ThreadException;
use League\ThreadPool\Interfaces\PoolItemInterface;
use League\ThreadPool\Interfaces\SystemCallsInterface;
use League\ThreadPool\Interfaces\ThreadInterface;
use League\ThreadPool\Interfaces\ThreadPoolInterface;
use SplQueue;


class ThreadPool implements ThreadPoolInterface
{
    /**
     * Count of maximum threads in pool
     *
     * @var int
     */
    protected $maxThreads;

    /**
     * Queue of active processes
     *
     * @var SplQueue
     */
    protected $threadPool;

    /**
     * Queue of scheduling processes
     *
     * @var SplQueue
     */
    protected $threadQueue;

    /**
     * Wrapper of system calls functions
     *
     * @var SystemCallsInterface
     */
    protected $systemCalls;

    /**
     * ThreadPool constructor.
     *
     * @param int $maxThreads
     * @param SystemCallsInterface $systemCalls
     */
    public function __construct(int $maxThreads = 0, SystemCallsInterface $systemCalls = null)
    {
        if ($systemCalls === null) {
            $systemCalls = new SystemCalls;
        }

        $this->systemCalls = $systemCalls;
        $this->maxThreads = $maxThreads;

        $this->threadPool = new SplQueue;
        $this->threadQueue = new SplQueue;

        $this->systemCalls->ignoreSignal(SIGCHLD);
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxThreads(int $maxThreads = 0): void
    {
        $this->maxThreads = $maxThreads;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(ThreadInterface $thread, callable $callback = null, $waitIfFull = false): bool
    {
        $poolItem = new PoolItem($thread, $callback);

        if ($this->maxThreads === 0 || $this->haveFreeSpaceInPool() === true) {
            return $this->startThread($poolItem);
        }

        if ($waitIfFull === false) {
            $this->threadQueue->enqueue($poolItem);

            return true;
        }

        while ($this->refresh()) {
            if ($this->haveFreeSpaceInPool() === true) {
                break;
            }

            $this->systemCalls->waitInterval();
        }

        return $this->startThread($poolItem);
    }

    /**
     * {@inheritdoc}
     */
    public function join($isSynchronous = true): bool
    {
        do {
            $this->refresh();

            $this->systemCalls->dispatchSignals();
        } while (
            $isSynchronous === true
            &&
            $this->threadPool->isEmpty() === false
        );

        return true;
    }

    /**
     * @return bool
     */
    protected function haveFreeSpaceInPool(): bool
    {
        return count($this->threadPool) < $this->maxThreads;
    }

    /**
     * Refresh pool of thread releases old threads and starts threads in queue
     */
    protected function refresh()
    {
        $threadsQueue = $this->threadPool;
        $this->threadPool = new SplQueue;

        while ($threadsQueue->isEmpty() === false) {
            $poolItem = $threadsQueue->dequeue();

            $isActive = $this->isActiveThreadInPoolItem($poolItem);

            if ($isActive === true) {
                $this->threadPool->enqueue($poolItem);

                continue;
            }

            $this->startQueued();
        }

        return true;
    }

    /**
     * Starts new thread immediately
     *
     * @param PoolItemInterface $poolItem
     *
     * @return bool
     */
    protected function startThread(PoolItemInterface $poolItem): bool
    {
        try {
            $isForked = $poolItem->getThread()->start();
        } catch (ThreadException $e) {
            return false;
        }

        if ($isForked === false) {
            return false;
        }

        $this->threadPool->enqueue($poolItem);

        return true;
    }

    /**
     * Checks if thread if pool item is active and call callback
     *
     * @param PoolItemInterface $poolItem
     *
     * @return bool
     */
    protected function isActiveThreadInPoolItem(PoolItemInterface $poolItem): bool
    {
        $isActive = $poolItem->getThread()->isActive();

        if ($isActive === true) {
            return true;
        }

        try {
            $exitCode = $poolItem->getThread()->read();
        } catch (ActiveThreadException $e) {
            return false;
        } catch (DeadProcessException $e) {
            $exitCode = null;
        }

        $callback = $poolItem->getCallback();
        if ($callback !== null) {
            try {
                $callback($exitCode);
            } catch (\Exception $e) {
                // normal way
            }
        }

        return false;
    }

    /**
     * Starts queued thread
     *
     * @return bool
     */
    protected function startQueued(): bool
    {
        if ($this->threadQueue->isEmpty() === true) {
            return false;
        }

        $poolItem = $this->threadQueue->dequeue();

        if ($poolItem === null) {
            return false;
        }

        return $this->startThread($poolItem);
    }
}
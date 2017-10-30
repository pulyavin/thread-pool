<?php

namespace League\ThreadPool;

use League\ThreadPool\Interfaces\PoolItemInterface;
use League\ThreadPool\Interfaces\ThreadInterface;


class PoolItem implements PoolItemInterface
{
    /**
     * @var ThreadInterface
     */
    private $thread;

    /**
     * @var callable
     */
    private $callback;

    /**
     * PoolItem constructor.
     *
     * @param ThreadInterface $thread
     * @param callable|null $callback
     */
    public function __construct(ThreadInterface $thread, callable $callback = null)
    {
        $this->thread = $thread;
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getThread(): ThreadInterface
    {
        return $this->thread;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallback()
    {
        return $this->callback;
    }
}
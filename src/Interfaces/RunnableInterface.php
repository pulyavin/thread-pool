<?php

namespace League\ThreadPool\Interfaces;

use League\ThreadPool\Exceptions\Thread\InterruptedException;


interface RunnableInterface
{
    /**
     * @return int Exit code
     *
     * @throws InterruptedException
     */
    public function run(): int;
}

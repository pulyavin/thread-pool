<?php

namespace Tests;

use League\ThreadPool\Interfaces\SystemCallsInterface;
use League\ThreadPool\Interfaces\ThreadInterface;
use League\ThreadPool\ThreadPool;
use PHPUnit\Framework\TestCase;


class ThreadPoolTest extends TestCase
{
    const STUB_EXIT_CODE = 123;

    private static $falseCounterForWaiting = 0;

    public static function isActiveWaitingCallback()
    {
        return self::$falseCounterForWaiting-- !== 0;
    }

    protected function getSystemCalls()
    {
        $systemCalls = $this->getMockBuilder(SystemCallsInterface::class)
            ->getMock();

        return $systemCalls;
    }

    protected function getThreadPool(SystemCallsInterface $systemCalls)
    {
        return new ThreadPool(0, $systemCalls);
    }

    public function testShould_PassCorrectExitCodeInCallable_When_CallableIsPassed()
    {
        $thread = $this->getMockBuilder(ThreadInterface::class)
            ->getMock();
        $thread
            ->method('start')
            ->willReturn(true);
        $thread
            ->method('isActive')
            ->willReturn(false);
        $thread
            ->method('read')
            ->willReturn(self::STUB_EXIT_CODE);

        $callback = $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $callback
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->equalTo(self::STUB_EXIT_CODE)
            );

        $systemCalls = $this->getSystemCalls();
        $threadPool = $this->getThreadPool($systemCalls);

        $isThreadSubmitted = $threadPool->submit($thread, $callback);
        $threadPool->join();

        $this->assertTrue($isThreadSubmitted);
    }

    public function testShould_EnqueueThreads_When_ThreadPoolIsFull()
    {
        $systemCalls = $this->getSystemCalls();
        $threadPool = $this->getThreadPool($systemCalls);
        $threadPool->setMaxThreads(2);

        for ($i = 1; $i < 4; $i++) {
            $exitCode = self::STUB_EXIT_CODE + $i;

            $thread = $this->getMockBuilder(ThreadInterface::class)
                ->getMock();
            $thread
                ->method('start')
                ->willReturn(true);
            $thread
                ->method('isActive')
                ->willReturn(false);
            $thread
                ->method('read')
                ->willReturn($exitCode);

            $callback = $this
                ->getMockBuilder(\stdClass::class)
                ->setMethods(['__invoke'])
                ->getMock();
            $callback
                ->expects($this->once())
                ->method('__invoke')
                ->with(
                    $this->equalTo($exitCode)
                );

            $isThreadSubmitted = $threadPool->submit($thread, $callback);

            $this->assertTrue($isThreadSubmitted);
        }

        $threadPool->join();
    }

    public function testShould_EnqueueThread_When_ItIsActiveInRefreshMethodFiveTimes()
    {
        $countTimes = 5;

        $systemCalls = $this->getSystemCalls();
        $threadPool = $this->getThreadPool($systemCalls);
        $threadPool->setMaxThreads(1);

        $thread = $this->getMockBuilder(ThreadInterface::class)
            ->getMock();
        $thread
            ->method('start')
            ->willReturn(true);
        $thread
            ->method('isActive')
            ->willReturn(true);
        $thread
            ->expects($this->exactly($countTimes))
            ->method('isActive')
            ->withAnyParameters();

        $isThreadSubmitted = $threadPool->submit($thread);
        $this->assertTrue($isThreadSubmitted);

        for ($i = 1; $i <= $countTimes; $i++) {
            $isTrue = $threadPool->join(false);
            $this->assertEquals($isTrue, true);
        }
    }

    public function testShould_Wait_When_SubmitInFullQueue()
    {
        self::$falseCounterForWaiting = 5;

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->expects($this->exactly(self::$falseCounterForWaiting))
            ->method('waitInterval')
            ->withAnyParameters();

        $threadPool = $this->getThreadPool($systemCalls);
        $threadPool->setMaxThreads(1);


        $activeThread = $this->getMockBuilder(ThreadInterface::class)
            ->getMock();
        $activeThread
            ->method('start')
            ->willReturn(true);
        $activeThread
            ->method('isActive')
            ->will($this->returnCallback([$this, 'isActiveWaitingCallback']));
        $activeThread
            ->method('read')
            ->willReturn(self::STUB_EXIT_CODE);

        $isTrue = $threadPool->submit($activeThread);
        $this->assertEquals($isTrue, true);


        $waitingThread = $this->getMockBuilder(ThreadInterface::class)
            ->getMock();
        $waitingThread
            ->method('start')
            ->willReturn(true);
        $waitingThread
            ->method('isActive')
            ->willReturn(false);
        $waitingThread
            ->method('read')
            ->willReturn(self::STUB_EXIT_CODE);
        $threadPool->submit($waitingThread, null, true);


        $threadPool->join();
    }
}
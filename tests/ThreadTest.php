<?php

namespace Tests;

use League\ThreadPool\Interfaces\RunnableInterface;
use League\ThreadPool\Interfaces\SystemCallsInterface;
use League\ThreadPool\Thread;
use PHPUnit\Framework\TestCase;


class ThreadTest extends TestCase
{
    const STUB_EXIT_CODE = 123;
    const STUB_PID = 35000;

    protected function getSystemCalls()
    {
        $systemCalls = $this->getMockBuilder(SystemCallsInterface::class)
            ->getMock();

        return $systemCalls;
    }

    protected function getRunnable() {
        return new class implements RunnableInterface
        {
            public function run(): int
            {
                return ThreadTest::STUB_EXIT_CODE;
            }
        };
    }

    protected function getThread(RunnableInterface $runnable, SystemCallsInterface $systemCalls)
    {
        return new Thread($runnable, $systemCalls);
    }

    public function testShould_ReturnCorrectExitCode_When_RunnableReturnCodeInSynchronous()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $thread = $this->getThread($runnable, $systemCalls);

        $thread->start(true);
        $exitCode = $thread->read();

        $this->assertEquals($exitCode, self::STUB_EXIT_CODE);
    }

    public function testShould_CorrectHandleExceptionCode_When_RunnableThrownException()
    {
        $runnable = new class implements RunnableInterface
        {
            public function run(): int
            {
                throw new \RuntimeException('', ThreadTest::STUB_EXIT_CODE);
            }
        };

        $systemCalls = $this->getSystemCalls();
        $thread = $this->getThread($runnable, $systemCalls);

        $thread->start(true);
        $exitCode = $thread->read();

        $this->assertEquals($exitCode, self::STUB_EXIT_CODE);
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Thread\WasLaunchedException
     */
    public function testShould_ThrowWasLaunchedException_When_ThreadLaunchedTwice()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $thread = $this->getThread($runnable, $systemCalls);
        $thread->start();
        $thread->start();
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Thread\CouldNotForkException
     */
    public function testShould_ThrowCouldNotForkException_When_ErrorWithForking()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(-1);

        $thread = $this->getThread($runnable, $systemCalls);
        $thread->start();
    }

    public function testShould_ReturnTrue_When_NonZeroPidIsReturnedInMainProcess()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(1);

        $thread = $this->getThread($runnable, $systemCalls);
        $true = $thread->start();

        $this->assertEquals($true, true);
    }

    public function testShould_CallExit_When_ZeroPidIsReturnedInChildProcess()
    {
        $runnable = new class implements RunnableInterface
        {
            public function run(): int
            {
                throw new \RuntimeException('', ThreadTest::STUB_EXIT_CODE);
            }
        };

        $processName = 'SomeNameOfProcess';

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(0);

        $systemCalls
            ->expects($this->once())
            ->method('setProcessTitle')
            ->with($this->equalTo($processName));

        $systemCalls
            ->expects($this->exactly(4))
            ->method('listenSignal')
            ->withConsecutive(
                [$this->equalTo(SIGTERM), $this->anything()],
                [$this->equalTo(SIGINT), $this->anything()],
                [$this->equalTo(SIGALRM), $this->anything()],
                [$this->equalTo(SIGHUP), $this->anything()]
            );

        $systemCalls
            ->expects($this->once())
            ->method('quit')
            ->with($this->equalTo(self::STUB_EXIT_CODE));

        $thread = $this->getThread($runnable, $systemCalls);
        $thread->setName($processName);
        $thread->start();
    }

    public function testShould_CorrectWork_When_StopMethodHandled()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(self::STUB_PID);

        $systemCalls
            ->method('isProcessRunning')
            ->willReturn(true);

        $systemCalls
            ->method('terminateProcess')
            ->willReturn(true);

        $systemCalls
            ->method('aSynchronousWaiting')
            ->willReturn(self::STUB_PID);

        $thread = $this->getThread($runnable, $systemCalls);
        $stopStatusBeforeStart = $thread->stop();

        $thread->start();
        $thread->stop();

        $this->assertEquals($stopStatusBeforeStart, false);
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Thread\ActiveThreadException
     */
    public function testShould_ThrowActiveThreadException_When_TryReadActiveThread()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(self::STUB_PID);

        $systemCalls
            ->method('isProcessRunning')
            ->willReturn(true);

        $systemCalls
            ->method('aSynchronousWaiting')
            ->willReturn(0);

        $thread = $this->getThread($runnable, $systemCalls);

        $thread->start();
        $thread->read();
    }

    public function testShould_SendTerminationSignal_When_TryStopThread()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(self::STUB_PID);

        $systemCalls
            ->method('isProcessRunning')
            ->willReturn(true);

        $systemCalls
            ->method('aSynchronousWaiting')
            ->willReturn(0);

        $systemCalls
            ->method('terminateProcess')
            ->willReturn(true);

        $systemCalls
            ->expects($this->once())
            ->method('terminateProcess')
            ->with(
                $this->equalTo(self::STUB_PID)
            );

        $thread = $this->getThread($runnable, $systemCalls);

        $thread->start();
        $isTrue = $thread->stop();

        $this->assertEquals($isTrue, true);
    }

    public function testShould_ReturnCorrectExitCode_When_SynchronousWaitAndRead()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(self::STUB_PID);

        $systemCalls
            ->method('synchronousWaiting')
            ->willReturn(self::STUB_PID);

        $systemCalls
            ->method('getExitCode')
            ->willReturn(self::STUB_EXIT_CODE);


        $thread = $this->getThread($runnable, $systemCalls);

        $thread->start();
        $isTrue = $thread->join(true);
        $exitCode = $thread->read();

        $this->assertEquals($exitCode, self::STUB_EXIT_CODE);
        $this->assertEquals($isTrue, true);
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Thread\DeadProcessException
     */
    public function testShould_ThrownException_When_ReadFromDeadProcess()
    {
        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('fork')
            ->willReturn(self::STUB_PID);

        $systemCalls
            ->method('isProcessRunning')
            ->willReturn(false);

        $thread = $this->getThread($runnable, $systemCalls);

        $thread->start();
        $thread->read();
    }


    public function testShould_ExecutesListOfCallbacks_When_ItPassed()
    {
        $firstCallback = $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $firstCallback
            ->expects($this->once())
            ->method('__invoke')
            ->withAnyParameters();

        $secondCallback = $this
            ->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $secondCallback
            ->expects($this->once())
            ->method('__invoke')
            ->withAnyParameters();

        $runnable = $this->getRunnable();

        $systemCalls = $this->getSystemCalls();

        $systemCalls
            ->method('fork')
            ->willReturn(0);

        $thread = $this->getThread($runnable, $systemCalls);

        $thread->registerShutdownFunction($firstCallback);
        $thread->registerShutdownFunction($secondCallback);
        $thread->start();
    }
}
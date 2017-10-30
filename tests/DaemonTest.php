<?php

namespace Tests;

use League\ThreadPool\Daemon;
use League\ThreadPool\ExitConstants;
use League\ThreadPool\Interfaces\FileSystemInterface;
use League\ThreadPool\Interfaces\SystemCallsInterface;
use League\ThreadPool\Interfaces\ThreadInterface;
use PHPUnit\Framework\TestCase;


class DaemonTest extends TestCase
{
    const STUB_PATH = 'some/pid/path';
    const STUB_PID = 35000;

    protected function getSystemCalls()
    {
        $systemCalls = $this->getMockBuilder(SystemCallsInterface::class)
            ->getMock();

        return $systemCalls;
    }

    protected function getFileSystem()
    {
        $fileSystem = $this->getMockBuilder(FileSystemInterface::class)
            ->getMock();

        return $fileSystem;
    }

    protected function getThread()
    {
        $thread = $this->getMockBuilder(ThreadInterface::class)
            ->getMock();

        return $thread;
    }

    protected function getDaemon(
        ThreadInterface $thread,
        SystemCallsInterface $systemCalls,
        FileSystemInterface $fileSystem
    )
    {
        return new Daemon($thread, self::STUB_PATH, $systemCalls, $fileSystem);
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Daemon\InvalidPidPathException
     */
    public function testShould_ThrowInvalidPidPathException_When_PidPathIsDirectory()
    {
        $systemCalls = $this->getSystemCalls();
        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isDirectory')
            ->willReturn(true);

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->start();
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Daemon\InvalidPidPathException
     */
    public function testShould_ThrowInvalidPidPathException_When_EmptyDirectoryName()
    {
        $systemCalls = $this->getSystemCalls();
        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isDirectory')
            ->willReturn(false);
        $fileSystem
            ->method('getDirectoryName')
            ->willReturn('');

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->start();
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Daemon\InvalidPidPathException
     */
    public function testShould_ThrowInvalidPidPathException_When_PidPathIsNotWritable()
    {
        $systemCalls = $this->getSystemCalls();
        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isDirectory')
            ->willReturn(false);
        $fileSystem
            ->method('getDirectoryName')
            ->willReturn('some');
        $fileSystem
            ->method('isWritable')
            ->willReturn(false);

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->start();
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Daemon\AlreadyStoppedException
     */
    public function testShould_ThrowAlreadyStoppedException_When_TryToStopStoppedDaemon()
    {
        $systemCalls = $this->getSystemCalls();
        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isFileExists')
            ->willReturn(false);

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->stop();
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Daemon\CantStopDaemonException
     */
    public function testShould_ThrowCantStopDaemonException_When_CantSendSigTermToDaemon()
    {
        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('terminateProcess')
            ->willReturn(false);
        $systemCalls
            ->expects($this->once())
            ->method('terminateProcess')
            ->with($this->equalTo(self::STUB_PID));

        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isFileExists')
            ->willReturn(true);
        $fileSystem
            ->method('fileGetContents')
            ->willReturn(self::STUB_PID);

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->stop();
    }

    /**
     * @expectedException \League\ThreadPool\Exceptions\Daemon\AlreadyRunningException
     */
    public function testShould_ThrowAlreadyRunningException_When_TryToStartRunningDaemon()
    {
        $systemCalls = $this->getSystemCalls();
        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isFileExists')
            ->willReturn(true);
        $fileSystem
            ->method('fileGetContents')
            ->willReturn(self::STUB_PID);

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->start();
    }

    public function testShould_ReturnCorrectPid_When_ItStoreInPidPath()
    {
        $systemCalls = $this->getSystemCalls();
        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isFileExists')
            ->willReturn(true);
        $fileSystem
            ->method('fileGetContents')
            ->willReturn(self::STUB_PID);

        $thread = $this->getThread();

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $pid = $daemon->getPid();

        $this->assertEquals($pid, self::STUB_PID);
    }

    public function testShould_CorrectDaemonizing_When_AllDataIsCorrect()
    {
        $isSynchronous = false;

        $systemCalls = $this->getSystemCalls();
        $systemCalls
            ->method('setSid')
            ->willReturn(0);
        $systemCalls
            ->expects($this->once())
            ->method('setSid')
            ->withAnyParameters();
        $systemCalls
            ->expects($this->once())
            ->method('quit')
            ->with($this->equalTo(ExitConstants::EXIT_SUCCESS));

        $fileSystem = $this->getFileSystem();
        $fileSystem
            ->method('isDirectory')
            ->willReturn(false);
        $fileSystem
            ->method('getDirectoryName')
            ->willReturn('some');
        $fileSystem
            ->method('isWritable')
            ->willReturn(true);
        $fileSystem
            ->method('isFileExists')
            ->willReturn(false);
        $fileSystem
            ->expects($this->once())
            ->method('closeHandlers')
            ->withAnyParameters();
        $fileSystem
            ->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->equalTo(self::STUB_PATH),
                $this->equalTo(self::STUB_PID)
            );

        $thread = $this->getThread();
        $thread
            ->method('start')
            ->willReturn(self::STUB_PID);
        $thread
            ->expects($this->once())
            ->method('start')
            ->with($this->equalTo($isSynchronous));

        $daemon = $this->getDaemon($thread, $systemCalls, $fileSystem);

        $daemon->start();
    }
}
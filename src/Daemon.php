<?php

namespace League\ThreadPool;

use League\ThreadPool\Exceptions\Daemon\AlreadyRunningException;
use League\ThreadPool\Exceptions\Daemon\AlreadyStoppedException;
use League\ThreadPool\Exceptions\Daemon\CantStopDaemonException;
use League\ThreadPool\Exceptions\Daemon\CouldNotForkException;
use League\ThreadPool\Exceptions\Daemon\DaemonException;
use League\ThreadPool\Exceptions\Daemon\InvalidPidPathException;
use League\ThreadPool\Exceptions\Thread\ThreadException;
use League\ThreadPool\Interfaces\DaemonInterface;
use League\ThreadPool\Interfaces\FileSystemInterface;
use League\ThreadPool\Interfaces\SystemCallsInterface;
use League\ThreadPool\Interfaces\ThreadInterface;


class Daemon implements DaemonInterface
{
    /**
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @var string
     */
    protected $pidPath;

    /**
     * Wrapper of system calls functions
     *
     * @var SystemCallsInterface
     */
    protected $systemCalls;

    /**
     * Wrapper of file system functions
     *
     * @var FileSystemInterface
     */
    protected $fileSystem;

    /**
     * Demon constructor.
     *
     * @param ThreadInterface $thread
     * @param $pidPath
     * @param SystemCallsInterface $systemCalls
     * @param FileSystemInterface $fileSystem
     */
    public function __construct(
        ThreadInterface $thread,
        $pidPath,
        SystemCallsInterface $systemCalls = null,
        FileSystemInterface $fileSystem = null
    )
    {
        if ($systemCalls === null) {
            $systemCalls = new SystemCalls;
        }

        if ($fileSystem === null) {
            $fileSystem = new FileSystem;
        }

        $this->thread = $thread;
        $this->pidPath = $pidPath;
        $this->systemCalls = $systemCalls;
        $this->fileSystem = $fileSystem;
    }

    /**
     * {@inheritdoc}
     */
    public function start($isSynchronous = false): bool
    {
        $runningPid = $this->getPid();
        if ($runningPid !== 0) {
            throw new AlreadyRunningException("Daemons already running with pid {$runningPid}");
        }

        $this->checkPidPath();

        if ($isSynchronous === false) {
            $pid = $this->systemCalls->fork();

            if ($pid === -1) {
                throw new CouldNotForkException;
            }

            // this is parent process
            if ($pid) {
                return true;
            }

            $this->fileSystem->closeHandlers();

            $sid = $this->systemCalls->setSid();

            if ($sid === -1) {
                $this->systemCalls->quit(ExitConstants::EXIT_CANT_SET_SID);
            }
        }

        $this->thread->registerShutdownFunction(function () {
            if ($this->fileSystem->isFileExists($this->pidPath)) {
                $this->fileSystem->delete($this->pidPath);
            }
        });

        try {
            $pid = $this->thread->start($isSynchronous);

            $this->fileSystem->filePutContents($this->pidPath, $pid);
        } catch (ThreadException $e) {
            throw new DaemonException("An error occurred \"{$e->getMessage()}\"");
        }

        $this->systemCalls->quit(ExitConstants::EXIT_SUCCESS);

        // never because exit will call
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPid(): int
    {
        if ($this->fileSystem->isFileExists($this->pidPath) === false) {
            return 0;
        }

        $pid = (int)$this->fileSystem->fileGetContents($this->pidPath);

        if ($pid <= 0) {
            return 0;
        }

        return $pid;
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): bool
    {
        $pid = $this->getPid();

        if ($pid === 0) {
            throw new AlreadyStoppedException('Daemon already stopped');
        }

        if ($this->systemCalls->terminateProcess($pid) === false) {
            throw new CantStopDaemonException("Can't send SIGTERM signal to daemon");
        }

        return true;
    }

    /**
     * Checks the current path to pid for the ability to create a file in it and write to it pid
     *
     * @throws InvalidPidPathException
     */
    protected function checkPidPath()
    {
        if ($this->fileSystem->isDirectory($this->pidPath)) {
            throw new InvalidPidPathException("Pid path \"{$this->pidPath}\" must be file");
        }

        $dir = $this->fileSystem->getDirectoryName($this->pidPath);

        if (empty($dir)) {
            throw new InvalidPidPathException("Invalid directory for pid path \"{$this->pidPath}\"");
        }

        if (!$this->fileSystem->isWritable($dir)) {
            throw new InvalidPidPathException("Directory \"{$this->pidPath}\" is not writable for this user");
        }
    }
}
<?php

namespace League\ThreadPool\Interfaces;


interface FileSystemInterface
{
    /**
     * Closes an open file pointer
     *
     * @return bool
     */
    public function closeHandlers(): bool;

    /**
     * Checks whether a file or directory exists
     *
     * @param $filename
     *
     * @return bool
     */
    public function isFileExists($filename): bool;

    /**
     * Reads entire file into a string
     *
     * @param $filename
     *
     * @return string|bool
     */
    public function fileGetContents($filename);

    /**
     * Write a string to a file
     *
     * @param $filename
     * @param $data
     *
     * @return int|bool
     */
    public function filePutContents($filename, $data);

    /**
     * Returns directory name component of path
     *
     * @param $path
     *
     * @return string
     */
    public function getDirectoryName($path): string;

    /**
     * Tells whether the filename is writable
     *
     * @param $filename
     *
     * @return bool
     */
    public function isWritable($filename): bool;

    /**
     * Tells whether the filename is a directory
     *
     * @param $filename
     *
     * @return bool
     */
    public function isDirectory($filename): bool;

    /**
     * Deletes a file
     *
     * @param $filename
     *
     * @return bool
     */
    public function delete($filename): bool;
}
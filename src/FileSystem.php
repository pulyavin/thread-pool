<?php

namespace League\ThreadPool;

use League\ThreadPool\Interfaces\FileSystemInterface;


class FileSystem implements FileSystemInterface
{
    /**
     * {@inheritdoc}
     */
    public function isFileExists($filename): bool
    {
        return file_exists($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function fileGetContents($filename)
    {
        return file_get_contents($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function filePutContents($filename, $data)
    {
        return file_put_contents($filename, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectoryName($path): string
    {
        return dirname($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($filename): bool
    {
        return is_writable($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($filename): bool
    {
        return is_dir($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function closeHandlers(): bool
    {
        if (fclose(STDIN) === false) {
            return false;
        }
        if (fclose(STDOUT) === false) {
            return false;
        }
        if (fclose(STDERR) === false) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($filename): bool
    {
        return unlink($filename);
    }
}
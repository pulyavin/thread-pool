# PHP Thread Pool

This library help to simply write PHP-code working in parallel processes


Installation
------------
0. Install [Composer](http://getcomposer.org/):

    ```
    curl -sS https://getcomposer.org/installer | php
    ```

0. Add the dependency:

    ```
    php composer.phar require pulyavin/thread-pool
    ```

Usage
-----

### Runnable

```php
use League\ThreadPool\Interfaces\RunnableInterface;

class WorkerRunnable extends RunnableInterface
{
    use \League\ThreadPool\RunnableTrait;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        try {
            while (true) {
                $this->logger->debug('We are working');
                $this->dispatchSignals();

                sleep(1);
            }
        }
        catch (InterruptedException $e) {
            $this->logger->debug('shutdown');

            return 0;
        }
        catch (\Exception $e) {
            return 1;
        }

        return 0;
    }
}

```


### Thread

```php
$runnable = new WorkerRunnable;

$thread = new \League\ThreadPool\Thread($runnable);
$thread->setName("my awesome process");
$thread->registerShutdownFunction(function () {
    unset("/var/run/process.pid");
});

$thread->start();
$thread->wait();
$thread->read();

$exitCode = $thread->readExitCode();
```


### ThreadPool

```php
$runnable = new WorkerRunnable;

$threadPoool = new \League\ThreadPool\ThreadPool;

for ($i = 0; $i < 5; $i++) {
    $thread = new \League\ThreadPool\Thread($runnable);
    $thread->setName("my awesome process #{$i}");
    
    $threadPoool->submit($thread);
}

$thread->join(true);
```


### Daemon

```php
$runnable = new WorkerRunnable;
$thread = new \League\ThreadPool\Thread($runnable);
$thread->setName("my awesome process");

$daemon = new League\ThreadPool\Daemon($thread, "/path/to/pid/file");
$daemon->run();
```


### Daemon as service

```php
#!/usr/bin/env php
<?php

require_once("./vendor/autoload.php");

$runnable = new WorkerRunnable;
$thread = new \League\ThreadPool\Thread($runnable);
$thread->setName("my awesome process");

$daemon = new \League\ThreadPool\Daemon($thread, "/path/to/pid/file");
$service = new \League\ThreadPool\Service\Service($daemon);

$service->run();
```

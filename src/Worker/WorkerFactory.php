<?php

namespace Poller\Worker;

use Amp\Parallel\Worker\BasicEnvironment;
use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Worker;
use Blackfire\Probe;
use Exception;

class WorkerFactory implements \Amp\Parallel\Worker\WorkerFactory
{
    /** @var string */
    private $className;

    /**
     * @param string $envClassName Name of class implementing \Amp\Parallel\Worker\Environment to instigate in each
     *     worker. Defaults to \Amp\Parallel\Worker\BasicEnvironment.
     *
     * @throws \Error If the given class name does not exist or does not implement \Amp\Parallel\Worker\Environment.
     */
    public function __construct(string $envClassName = BasicEnvironment::class)
    {
        if (!\class_exists($envClassName)) {
            throw new \Error(\sprintf("Invalid environment class name '%s'", $envClassName));
        }

        if (!\is_subclass_of($envClassName, Environment::class)) {
            throw new \Error(\sprintf(
                "The class '%s' does not implement '%s'",
                $envClassName,
                Environment::class
            ));
        }

        $this->className = $envClassName;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(): Worker
    {
        return new WorkerProcess(
            $this->className,
            [],
            \getenv("AMP_PHP_BINARY") ?: (\defined("AMP_PHP_BINARY") ? \AMP_PHP_BINARY : null)
        );
    }
}

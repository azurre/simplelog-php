<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component;

use Azurre\Component\Logger\Handler\HandlerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Class Logger
 */
class Logger implements \Psr\Log\LoggerInterface
{
    use LoggerTrait;

    /**
     * Log channel--namespace for log lines.
     * Used to identify and correlate groups of similar log lines.
     *
     * @var string
     */
    protected $channel;

    /**
     * @var HandlerInterface[]
     */
    protected $handlers = [];

    /** @var int Lowest log level to log */
    protected $logLevel;

    const DEFAULT_CHANNEL = 'default';

    const LOG_LEVEL_PRIORITY = [
        LogLevel::DEBUG     => 1,
        LogLevel::INFO      => 2,
        LogLevel::NOTICE    => 3,
        LogLevel::WARNING   => 4,
        LogLevel::ERROR     => 5,
        LogLevel::CRITICAL  => 6,
        LogLevel::ALERT     => 7,
        LogLevel::EMERGENCY => 8
    ];

    /**
     * Logger constructor
     *
     * @param string $channel  Logger channel associated with this logger.
     * @param string $logLevel (optional) Lowest log level to log.
     */
    public function __construct($channel = null, $logLevel = LogLevel::DEBUG)
    {
        $this->channel = $channel ?: static::DEFAULT_CHANNEL;
        $this->setLogLevel($logLevel);
    }

    /**
     * Set the lowest log level to log.
     *
     * @param string $logLevel
     */
    public function setLogLevel($logLevel)
    {
        if (!array_key_exists($logLevel, static::LOG_LEVEL_PRIORITY)) {
            throw new \RuntimeException("Log level $logLevel is not supported");
        }

        $this->logLevel = static::LOG_LEVEL_PRIORITY[$logLevel];
    }

    /**
     * Set the log channel which identifies the log line.
     *
     * @param string $channel
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Log a message.
     * Generic log routine that all severity levels use to log an event.
     *
     * @param string $level
     * @param string $message Content of log event.
     * @param array  $data    Potentially multidimensional associative array of support data that goes with the log event.
     */
    public function log($level, $message = '', array $data = null)
    {
        if (!$this->logAtThisLevel($level)) {
            return;
        }
        $handlers = $this->getHandlers();
        if (empty($handlers)) {
            $handlers[] = new Logger\Handler\Stdout;
        }
        /** @var Logger\Handler\HandlerInterface $handler */
        foreach ($handlers as $handler) {
            $handler->handle($this->channel, $level, $message, $data);
        }
    }

    /**
     * @param HandlerInterface|string $handler
     */
    public function setHandler($handler)
    {
        $this->handlers = [];
        $this->addHandler($handler);
    }

    /**
     * @param HandlerInterface|string $handler
     * @return $this
     */
    public function addHandler($handler)
    {
        if (\is_string($handler) && \class_exists($handler)) {
            $handler = new $handler;
        }
        if (!$handler instanceof Logger\Handler\HandlerInterface) {
            throw new \RuntimeException('Handler must be an instance of ' . HandlerInterface::class);
        }
        $handler->setLogger($this);
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * Determine if the logger should log at a certain log level.
     *
     * @param  string $level
     * @return bool   True if we log at this level; false otherwise.
     */
    public function logAtThisLevel($level)
    {
        return static::LOG_LEVEL_PRIORITY[$level] >= $this->logLevel;
    }

    /**
     * @return Logger\Handler\HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return getmypid();
    }
}

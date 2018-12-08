<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @date   08.12.2018
 */

namespace Azurre\Component;

/**
 * Class Logger
 */
class Logger implements \Psr\Log\LoggerInterface
{
    /**
     * Log channel--namespace for log lines.
     * Used to identify and correlate groups of similar log lines.
     *
     * @var string
     */
    protected $channel;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * Lowest log level to log
     *
     * @var int
     */
    protected $logLevel;

    /**
     * Default channel
     */
    const DEFAULT_CHANNEL = 'default';

    /**
     * Log level priority
     */
    protected static $levels = [
        \Psr\Log\LogLevel::DEBUG     => 1,
        \Psr\Log\LogLevel::INFO      => 2,
        \Psr\Log\LogLevel::NOTICE    => 3,
        \Psr\Log\LogLevel::WARNING   => 4,
        \Psr\Log\LogLevel::ERROR     => 5,
        \Psr\Log\LogLevel::CRITICAL  => 6,
        \Psr\Log\LogLevel::ALERT     => 7,
        \Psr\Log\LogLevel::EMERGENCY => 8
    ];

    /**
     * Logger constructor
     *
     * @param string $channel  Logger channel associated with this logger.
     * @param string $logLevel (optional) Lowest log level to log.
     */
    public function __construct($channel = null, $logLevel = \Psr\Log\LogLevel::DEBUG)
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
        if (!array_key_exists($logLevel, self::$levels)) {
            throw new \RuntimeException("Log level {$logLevel} is not supported");
        }

        $this->logLevel = self::$levels[$logLevel];
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
            $handlers[] = $handler = Logger\Handler\File::class;
            $this->addHandler($handler);
        }
        /** @var Logger\Handler\HandlerInterface $handler */
        foreach ($handlers as $handler) {
            $handler->setLogger($this)->handle($this->channel, $level, $message, $data);
        }
    }

    /**
     * Log a debug message.
     * Fine-grained informational events that are most useful to debug an application.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     * @throws \Exception
     */
    public function debug($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::DEBUG, $message, $data);
    }

    /**
     * Log an info message.
     * Interesting events and informational messages that highlight the progress of the application at coarse-grained level.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     */
    public function info($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::INFO, $message, $data);
    }

    /**
     * Log an notice message.
     * Normal but significant events.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     * @throws \Exception
     */
    public function notice($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::NOTICE, $message, $data);
    }

    /**
     * Log a warning message.
     * Exceptional occurrences that are not errors--undesirable things that are not necessarily wrong.
     * Potentially harmful situations which still allow the application to continue running.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     * @throws \Exception
     */
    public function warning($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::WARNING, $message, $data);
    }

    /**
     * Log an error message.
     * Error events that might still allow the application to continue running.
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     */
    public function error($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::ERROR, $message, $data);
    }

    /**
     * Log a critical condition.
     * Application components being unavailable, unexpected exceptions, etc.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     * @throws \Exception
     */
    public function critical($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::CRITICAL, $message, $data);
    }

    /**
     * Log an alert.
     * This should trigger an email or SMS alert and wake you up.
     * Example: Entire site down, database unavailable, etc.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     * @throws \Exception
     */
    public function alert($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::ALERT, $message, $data);
    }

    /**
     * Log an emergency.
     * System is unsable.
     * This should trigger an email or SMS alert and wake you up.
     *
     * @param string $message Content of log event.
     * @param array  $data    Associative array of contextual support data that goes with the log event.
     * @throws \Exception
     */
    public function emergency($message = '', array $data = null)
    {
        $this->log(\Psr\Log\LogLevel::EMERGENCY, $message, $data);
    }

    /**
     * @param Object|string $handler
     */
    public function setHandler($handler)
    {
        $this->handlers = [];
        $this->addHandler($handler);
    }

    /**
     * @param Object|string $handler
     * @return $this
     */
    public function addHandler($handler)
    {
        if (\is_string($handler) && \class_exists($handler)) {
            $handler = new $handler;
        }
        if (!\is_object($handler) || !$handler instanceof Logger\Handler\HandlerInterface) {
            throw new \RuntimeException('Handler must be an instance of HandlerInterface');
        }
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
        return self::$levels[$level] >= $this->logLevel;
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

    /**
     * @param string $level
     * @return array
     */
    public static function getLevels($level = null)
    {
        if ($level) {
            return isset(static::$levels[$level]) ? static::$levels[$level] : null;
        }

        return static::$levels;
    }
}

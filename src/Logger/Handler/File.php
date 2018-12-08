<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @date   08.12.2018
 */

namespace Azurre\Component\Logger\Handler;

/**
 * Class File
 */
class File implements HandlerInterface
{
    /**
     * Log fields separated by tabs to form a TSV (CSV with tabs).
     */
    const TAB = "\t";

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @var bool
     */
    protected $stdout;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * File constructor.
     *
     * @param string $logFile
     * @param bool   $stdout
     */
    public function __construct($logFile = 'default.log', $stdout = false)
    {
        $this->logFile = $logFile;
        $this->stdout = $stdout;
    }

    /**
     * @inheritdoc
     */
    public function handle($channel, $level, $message = '', array $data = null)
    {
        $pid = getmypid();
        list($exception, $data) = $this->handleException($data);
        $dataString = $data ? json_encode($data, \JSON_UNESCAPED_SLASHES) : '{}';
        $logLine = $this->formatLogLine($channel, $level, $pid, $message, $dataString, $exception);

        $res = @file_put_contents($this->logFile, $logLine, FILE_APPEND);
        if ($res === false) {
            throw new \RuntimeException("Cannot write to {$this->logFile}");
        }

        // Log to stdout if option set to do so.
        if ($this->stdout) {
            print($logLine);
        }
    }

    /**
     * Handle an exception in the data context array.
     * If an exception is included in the data context array, extract it.
     *
     * @param  array $data
     * @return array  [exception, data (without exception)]
     */
    protected function handleException(array $data = null)
    {
        if (isset($data['exception']) && $data['exception'] instanceof \Throwable) {
            $exception = $data['exception'];
            $exception_data = $this->buildExceptionData($exception);
            unset($data['exception']);
        } else {
            $exception_data = '{}';
        }

        return [$exception_data, $data];
    }

    /**
     * Build the exception log data.
     *
     * @param  \Throwable $e
     * @return string JSON {message, code, file, line, trace}
     */
    protected function buildExceptionData(\Throwable $e)
    {
        return json_encode(
            [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace()
            ],
            \JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Format the log line.
     * YYYY-mm-dd HH:ii:ss.uuuuuu  [loglevel]  [channel]  [pid:##]  Log message content  {"Optional":"JSON Contextual Support Data"}  {"Optional":"Exception Data"}
     *
     * @param  string $channel
     * @param  string $level
     * @param  int    $pid
     * @param  string $message
     * @param  string $data
     * @param  string $exceptionData
     * @return string
     */
    protected function formatLogLine($channel, $level, $pid, $message, $data, $exceptionData)
    {
        $time = microtime(true);
        $timeFormatted = sprintf("%06d", ($time - floor($time)) * 1000000);
        $dt = new \DateTime(date('Y-m-d H:i:s.' . $timeFormatted, $time));
        $time = $dt->format('Y-m-d H:i:s.u');

        return
            $time . self::TAB .
            "[{$level}]" . self::TAB .
            "[{$channel}]" . self::TAB .
            "[pid:{$pid}]" . self::TAB .
            str_replace(["\r\n", "\n", "\r"], '\n', trim($message)) . self::TAB .
            str_replace(["\r\n", "\n", "\r"], '\n', $data) . self::TAB .
            str_replace(["\r\n", "\n", "\r"], '\n', $exceptionData) . \PHP_EOL;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @return $this
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param bool $stdout
     * @return $this
     */
    public function setStdout($stdout)
    {
        $this->stdout = $stdout;

        return $this;
    }
}

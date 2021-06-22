<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component\Logger\Handler;

/**
 * Class File
 */
class File implements HandlerInterface
{
    use \Psr\Log\LoggerAwareTrait;
    use WithLogLineFormatterTrait;
    use HandleExceptionTrait;

    /** @var string */
    protected $logFile;

    /**
     * File constructor.
     *
     * @param string $logFile
     */
    public function __construct($logFile = 'default.log')
    {
        $this->logFile = $logFile;
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

        $res = @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        if ($res === false) {
            throw new \RuntimeException("Cannot write to $this->logFile");
        }
    }
}

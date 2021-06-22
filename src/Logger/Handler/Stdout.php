<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component\Logger\Handler;

use Azurre\Component\Logger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;

/**
 * Class Stdout
 */
class Stdout implements HandlerInterface
{
    use LoggerAwareTrait;
    use WithLogLineFormatterTrait;
    use HandleExceptionTrait;

    /**
     * @inheritDoc
     */
    public function handle($channel, $level, $message = '', array $data = null)
    {
        $pid = getmypid();
        list($exception, $data) = $this->handleException($data);
        $dataString = $data ? json_encode($data, \JSON_UNESCAPED_SLASHES) : '{}';
        $std = Logger::LOG_LEVEL_PRIORITY[$level] >= Logger::LOG_LEVEL_PRIORITY[LogLevel::ERROR] ? 'php://stderr' : 'php://stdout';
        file_put_contents($std, $this->formatLogLine($channel, $level, $pid, $message, $dataString, $exception));
        return $this;
    }
}

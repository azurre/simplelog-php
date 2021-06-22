<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component\Logger\Handler;

/**
 * Trait WithLogLineFormatter
 */
trait WithLogLineFormatterTrait
{
    /**
     * Format the log line.
     * YYYY-mm-dd HH:ii:ss.uuuuuu  [loglevel]  [channel]  [pid:##]  Log message content  {"Optional":"JSON Contextual Support Data"}  {"Optional":"Exception Data"}
     *
     * @param string $channel
     * @param string $level
     * @param int $pid
     * @param string $message
     * @param string $data
     * @param string $exceptionData
     * @return string
     * @throws \Exception
     */
    protected function formatLogLine($channel, $level, $pid, $message, $data, $exceptionData)
    {
        $time = microtime(true);
        $timeFormatted = sprintf("%06d", ($time - floor($time)) * 1000000);
        $dt = new \DateTime(date('Y-m-d H:i:s.' . $timeFormatted, $time));
        $time = $dt->format('Y-m-d H:i:s.u');

        return
            "$time\t" .
            "[$level]\t" .
            "[$channel]\t" .
            "[pid:$pid]\t" .
            str_replace(["\r\n", "\n", "\r"], '\n', trim($message)) ."\t".
            str_replace(["\r\n", "\n", "\r"], '\n', $data) . "\t" .
            str_replace(["\r\n", "\n", "\r"], '\n', $exceptionData) . \PHP_EOL;
    }
}

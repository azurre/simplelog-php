<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component\Logger\Handler;

/**
 * Logs handler interface
 */
interface HandlerInterface extends \Psr\Log\LoggerAwareInterface
{
    /**
     * @param string $channel
     * @param string $level
     * @param string $message
     * @param array  $data
     * @return $this
     */
    public function handle($channel, $level, $message = '', array $data = null);
}

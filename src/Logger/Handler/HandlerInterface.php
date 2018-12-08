<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @date   08.12.2018
 */

namespace Azurre\Component\Logger\Handler;

/**
 * File handler interface
 */
interface HandlerInterface
{
    /**
     * @param string $channel
     * @param string $level
     * @param string $message
     * @param array  $data
     * @return $this
     */
    public function handle($channel, $level, $message = '', array $data = null);

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger();

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @return $this
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger);
}

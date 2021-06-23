<?php
/**
 * @author Alex Milenin
 * @email  admin@azrr.info
 * @copyright Copyright (c)Alex Milenin (https://azrr.info/)
 */

namespace Azurre\Component\Logger\Handler;

trait HandleExceptionTrait
{
    /**
     * Handle an exception in the data context array.
     * If an exception is included in the data context array, extract it.
     *
     * @param  array $data
     * @return array  [exception, data (without exception)]
     */
    protected function handleException(array $data = null)
    {
        if (isset($data['exception']) && $data['exception'] instanceof \Exception) {
            $exception_data = $this->buildExceptionData($data['exception']);
            unset($data['exception']);
        } else {
            $exception_data = '{}';
        }

        return [$exception_data, $data];
    }

    /**
     * Build the exception log data.
     *
     * @param  \Exception $e
     * @return string JSON {message, code, file, line, trace}
     */
    protected function buildExceptionData(\Exception $e)
    {
        return json_encode(
            [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ],
            \JSON_UNESCAPED_SLASHES
        );
    }
}

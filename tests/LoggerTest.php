<?php
use Azurre\Component\Logger;
use Psr\Log\LogLevel;
use Azurre\Component\Logger\Handler\File;

/**
 * Unit tests for SimpleLog\Logger.
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Log file
     *
     * @var string
     */
    private $logfile;

    /**
     * @var \Azurre\Component\Logger $logger
     */
    private $logger;

    const TEST_CHANNEL = 'unittest';
    const TEST_MESSAGE = 'Log message goes here.';

    const TEST_LOG_REGEX = "/^
        \d{4}-\d{2}-\d{2} [ ] \d{2}:\d{2}:\d{2}[.]\d{6}    # Timestamp (YYYY-mm-dd HH:ii:ss.uuuuuu)
        \s
        \[\w+\]                                            # [loglevel]
        \s
        \[unittest\]                                       # [channel]
        \s
        \[pid:\d+\]                                        # [pid:1234]
        \s
        Log [ ] message [ ] goes [ ] here.                 # Log message
        \s
        {.*}                                               # Data
        \s
        {.*}                                               # Exception data
    /x";

    /**
     * Set up test by instantiating a logger writing to a temporary file.
     */
    public function setUp()
    {
        $this->logfile = tempnam('/tmp', 'SimpleLogUnitTest');

        if (file_exists($this->logfile)) {
            unlink($this->logfile);
        }
        $this->logger = new Logger(self::TEST_CHANNEL);
        $this->logger->setHandler(new File($this->logfile));
    }

    /**
     * Clean up test by removing temporary log file.
     *
     * @return void
     */
    public function tearDown()
    {
        if (file_exists($this->logfile)) {
            unlink($this->logfile);
        }
    }

    /**
     * @testCase Constructor makes a SimpleLog\Logger
     */
    public function testLoggerIsSimpleLogLogger()
    {
        $this->assertInstanceOf(Logger::class, $this->logger);
    }

    /**
     * @testCase Logger implements PSR-3 Psr\Log\LoggerInterface
     */
    public function testLoggerImplementsPRS3Interface()
    {
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $this->logger);
    }

    /**
     * @testCase Constructor sets expected properties.
     * @throws \ReflectionException
     */
    public function testConstructorSetsProperties()
    {
        $channelProperty = new \ReflectionProperty(Logger::class, 'channel');
        $logLevelProperty = new \ReflectionProperty(Logger::class, 'logLevel');

        $channelProperty->setAccessible(true);
        $logLevelProperty->setAccessible(true);

        $this->assertEquals(self::TEST_CHANNEL, $channelProperty->getValue($this->logger));
        $this->assertEquals(Logger::getLevels(LogLevel::DEBUG), $logLevelProperty->getValue($this->logger));
    }

    /**
     * @testCase     setLogLevel sets the correct log level.
     * @dataProvider dataProviderForSetLogLevel
     * @param string $logLevel
     * @param int    $logLevelCode
     * @throws \ReflectionException
     */
    public function testSetLogLevelUsingConstants($logLevel, $logLevelCode)
    {
        $this->logger->setLogLevel($logLevel);

        $logLevelProperty = new \ReflectionProperty(Logger::class, 'logLevel');
        $logLevelProperty->setAccessible(true);

        $this->assertEquals($logLevelCode, $logLevelProperty->getValue($this->logger));
    }

    public function dataProviderForSetLogLevel()
    {
        $levels = Logger::getLevels();

        return [
            [LogLevel::DEBUG, $levels[LogLevel::DEBUG]],
            [LogLevel::INFO, $levels[LogLevel::INFO]],
            [LogLevel::NOTICE, $levels[LogLevel::NOTICE]],
            [LogLevel::WARNING, $levels[LogLevel::WARNING]],
            [LogLevel::ERROR, $levels[LogLevel::ERROR]],
            [LogLevel::CRITICAL, $levels[LogLevel::CRITICAL]],
            [LogLevel::ALERT, $levels[LogLevel::ALERT]],
            [LogLevel::EMERGENCY, $levels[LogLevel::EMERGENCY]],
        ];
    }

    /**
     * @testCase setLogLevel throws a \DomainException when set to an invalid log level.
     */
    public function testSetLogLevelWithBadLevelException()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->logger->setLogLevel('ThisLogLevelDoesNotExist');
    }


    /**
     * @testCase     setChannel sets the channel property.
     * @dataProvider dataProviderForSetChannel
     * @param        string $channel
     * @throws \ReflectionException
     */
    public function testSetChannel($channel)
    {
        $channelProperty = new \ReflectionProperty(Logger::class, 'channel');
        $channelProperty->setAccessible(true);

        $this->logger->setChannel($channel);
        $this->assertEquals($channel, $channelProperty->getValue($this->logger));
    }

    /**
     * @return array
     */
    public function dataProviderForSetChannel()
    {
        return [
            ['newchannel'],
            ['evennewerchannel'],
        ];
    }

    /**
     * @testCase     setOutput sets the stdout property.
     * @dataProvider dataProviderForSetOutput
     * @param        bool $output
     * @throws \ReflectionException
     */
    public function testSetOutput($output)
    {
        $stdoutProperty = new \ReflectionProperty(\Azurre\Component\Logger\Handler\File::class, 'stdout');
        $stdoutProperty->setAccessible(true);

        $handlers = $this->logger->getHandlers();
        $handler = reset($handlers);
        $handler->setStdout($output);
        $this->assertEquals($output, $stdoutProperty->getValue($handler));
    }

    /**
     * @return array
     */
    public function dataProviderForSetOutput()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @testCase     Logger creates properly formatted log lines with the right log level.
     * @dataProvider dataProviderForLogging
     * @param string $logLevel
     */
    public function testLogging($logLevel)
    {
        $this->logger->$logLevel(self::TEST_MESSAGE);
        $logLine = trim(file_get_contents($this->logfile));
        $this->assertTrue((bool)preg_match(self::TEST_LOG_REGEX, $logLine));
        $this->assertTrue((bool)preg_match("/\[$logLevel\]/", $logLine));
    }

    /**
     * @return array
     */
    public function dataProviderForLogging()
    {
        return [
            ['debug'],
            ['info'],
            ['notice'],
            ['warning'],
            ['error'],
            ['critical'],
            ['alert'],
            ['emergency'],
        ];
    }

    /**
     * @testCase Data context array shows up as a JSON string.
     * @throws \Exception
     */
    public function testDataContext()
    {
        $this->logger->info(self::TEST_MESSAGE, ['key1' => 'value1', 'key2' => 6]);
        $logLine = trim(file_get_contents($this->logfile));
        $this->assertTrue((bool)preg_match('/\s{"key1":"value1","key2":6}\s/', $logLine));
    }

    /**
     * @testCase Logging an exception
     * @throws \Exception
     */
    public function testExceptionTextWhenLoggingErrorWithExceptionData()
    {
        try {
            throw new \Exception('Exception123');
        } catch (\Exception $e) {
            $this->logger->error('Testing the Exception', ['exception' => $e]);
            $logLine = trim(file_get_contents($this->logfile));
            $this->assertTrue(false !== strpos($logLine, 'Testing the Exception'));
            $this->assertTrue(false !== strpos($logLine, 'Exception123'));
            $this->assertTrue(false !== strpos($logLine, 'code'));
            $this->assertTrue(false !== strpos($logLine, 'file'));
            $this->assertTrue(false !== strpos($logLine, 'line'));
            $this->assertTrue(false !== strpos($logLine, 'trace'));
        }
    }

    /**
     * @testCase Log lines will be on a single line even if there are newline characters in the log message.
     * @throws \Exception
     */
    public function testLogMessageIsOneLineEvenThoughItHasNewLineCharacters()
    {
        $this->logger->info("This message has a new line\nAnd another\n", ['key' => 'value']);
        $logLines = file($this->logfile);
        $this->assertCount(1, $logLines);
    }

    /**
     * @testCase Log lines will be on a single line even if there are newline characters in the log message.
     * @throws \Exception
     */
    public function testLogMessageIsOneLineEvenThoughItHasNewLineCharactersInData()
    {
        $this->logger->info('Log message', ['key' => "Value\nwith\new\lines\n"]);
        $logLines = file($this->logfile);
        $this->assertCount(1, $logLines);
    }

    /**
     * @testCase Log lines will be on a single line even if there are newline characters in the exception.
     * @throws \Exception
     */
    public function testLogMessageIsOneLineEvenThoughItHasNewLineCharactersInException()
    {
        $this->logger->info('Log message',
            ['key' => 'value', 'exception' => new \Exception("This\nhas\newlines\nin\nit")]);
        $logLines = file($this->logfile);
        $this->assertCount(1, $logLines);
    }

    /**
     * @testCase Minimum log levels determine what log levels get logged.
     * @throws \Exception
     */
    public function testMinimumLogLevels()
    {
        $this->logger->setLogLevel(LogLevel::ERROR);

        $this->logger->debug('This will not be logged.');
        $this->logger->info('This will not be logged.');
        $this->logger->notice('This will not be logged.');
        $this->logger->warning('This will not be logged.');

        $this->logger->error('This will be logged.');
        $this->logger->critical('This will be logged.');
        $this->logger->alert('This will be logged.');
        $this->logger->emergency('This will be logged.');

        $logLines = file($this->logfile);
        $this->assertCount(4, $logLines);
    }

    /**
     * @testCase Minimum log levels determine what log levels get logged.
     * @throws \Exception
     */
    public function testMinimumLogLevelsByCheckingFileExists()
    {
        $this->logger->setLogLevel(LogLevel::ERROR);

        $this->logger->debug('This will not be logged.');
        $this->logger->info('This will not be logged.');
        $this->logger->notice('This will not be logged.');
        $this->logger->warning('This will not be logged.');
        $this->assertFileNotExists($this->logfile);

        $this->logger->error('This will be logged.');
        $this->assertFileExists($this->logfile);
    }

    /**
     * @testCase Exception is thrown if the log file cannot be opened for appending.
     * @throws \Exception
     */
    public function testLogExceptionCannotOpenFileForWriting()
    {
        $file = '/this/file/should/not/exist/on/any/system/if/it/does/well/oh/well/this/test/will/fail/logfile123.loglog.log';
        $badLogger = new Logger(self::TEST_CHANNEL);
        $badLogger->setHandler(new File($file));
        $this->setExpectedException(\RuntimeException::class);
        $badLogger->info('This is not going to work, hence the test for the exception!');
    }

    /**
     * @testCase After setting output to true the logger will output log lines to STDOUT.
     * @throws \Exception
     */
    public function testLoggingToStdOut()
    {
        $handlers = $this->logger->getHandlers();
        $handler = reset($handlers);
        $handler->setStdout(true);
        $this->expectOutputRegex('/^\d{4}-\d{2}-\d{2} [ ] \d{2}:\d{2}:\d{2}[.]\d{6} \s \[\w+\] \s \[\w+\] \s \[pid:\d+\] \s Test Message \s {.*} \s {.*}/x');
        $this->logger->info('TestMessage');
    }
}

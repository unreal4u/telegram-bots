<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use unreal4u\TelegramBots\Bots\Base;

class bootstrap {
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var string
     */
    private $botName = '';

    /**
     * Sets up the /dev/null logger for all tests
     *
     * @return bootstrap
     */
    public function setupLogger(): bootstrap
    {
        $this->logger = new Logger('unittests');
        $this->logger->pushHandler(new StreamHandler('/dev/null', Logger::WARNING));

        return $this;
    }

    /**
     * Sets the botname dynamically based on the given bot
     *
     * @param Base $bot
     * @return bootstrap
     */
    public function setupBot(Base $bot): bootstrap
    {
        $reflect = new ReflectionClass($bot);
        $this->botName = $reflect->getShortName();

        return $this;
    }

    /**
     * Ability to overwrite above behaviour, only used in scenarios where we can't give a bot
     *
     * @see setupBot
     * @param string $botName
     * @return bootstrap
     */
    public function forceConfigurationSet(string $botName): bootstrap
    {
        $this->botName = $botName;

        return $this;
    }

    /**
     * Will return the /dev/null logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getSimulatedPostData(string $command): array
    {
        $filename = 'tests/commandEmulator/Bots/'.$this->botName.'/'.$command.'.json';
        if (!file_exists($filename)) {
            throw new \Exception(sprintf('Command emulator file "%s" not found', $filename));
        }

        $rawData = file_get_contents($filename);
        return json_decode($rawData, true);
    }
}

/*
 * Hackish, but it is only a test, no need to do this for every time this class is initialized
 */
chdir(dirname(__FILE__).'/../');
include 'vendor/autoload.php';

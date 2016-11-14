<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use unreal4u\TelegramBots\Bots\BotsImplementation;

class bootstrap {
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var string
     */
    private $botName = '';

    public function __construct()
    {
        chdir(dirname(__FILE__).'/../');
        include 'vendor/autoload.php';
    }

    public function setupLogger(): bootstrap
    {
        $this->logger = new Logger('unittests');
        $this->logger->pushHandler(new StreamHandler('/dev/null', Logger::WARNING));

        return $this;
    }

    public function setupBot(BotsImplementation $bot): bootstrap
    {
        $reflect = new ReflectionClass($bot);
        $this->botName = $reflect->getShortName();

        return $this;
    }

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

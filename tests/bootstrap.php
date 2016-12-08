<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use unreal4u\TelegramBots\Bots\Base;
use unreal4u\TelegramBots\DatabaseWrapper;
use unreal4u\TelegramBots\Models\Entities\Events;
use unreal4u\TelegramBots\Models\Entities\Monitors;

class bootstrap {
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    private $botName = '';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Sets up the /dev/null logger for all tests
     *
     * @return bootstrap
     */
    public function setupLogger(): bootstrap
    {
        $this->logger = new Logger('unittests');
        $this->logger->pushHandler(new StreamHandler('/dev/null', Logger::CRITICAL));
        // Handy for debugging unit test problems
        #$this->logger->pushHandler(new StreamHandler('telegramApiLogs/unit-tests.log', Logger::DEBUG));

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

    public function getSimulatedPostData(string $command, string $subcommand = ''): array
    {
        if (!empty($subcommand)) {
            $command = $command.'-'.$subcommand;
        }
        $filename = 'tests/commandEmulator/Bots/'.$this->botName.'/'.$command.'.json';
        if (!file_exists($filename)) {
            throw new \Exception(sprintf('Command emulator file "%s" not found', $filename));
        }

        $rawData = file_get_contents($filename);
        return json_decode($rawData, true);
    }

    public function setupSQLiteDatabase(): bootstrap
    {
        $this->logger->debug('Setting up SQLite database');
        $wrapper = new DatabaseWrapper($this->logger);
        $this->entityManager = $wrapper->getEntity('UptimeMonitorBot');

        $tool = new SchemaTool($this->entityManager);
        $classes = [
            $this->entityManager->getClassMetadata(Monitors::class),
            $this->entityManager->getClassMetadata(Events::class),
        ];
        $tool->createSchema($classes);
        $this->logger->debug('Importing all entities');
        $this->entityManager->flush();

        $this->logger->debug('Filling in default test data');
        $this->fillDefaultSQLiteData();
        return $this;
    }

    private function fillDefaultSQLiteData(): bootstrap
    {
        $monitor = new Monitors();
        $monitor->setUserId(12345678);
        $monitor->setChatId(12341234);
        $monitor->setNotifyUrl('777b7777-7bb7-7b77-777b-bb7fd7777b77');
        $this->entityManager->persist($monitor);
        $this->entityManager->flush();

        return $this;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }
}

/*
 * Hackish, but as this only gets loaded when performing unit tests, there is no need to do this for every time the
 * class is initialized (which is on every test). Instead, perform the following operations only when this file is
 * included
 */
date_default_timezone_set('UTC');
chdir(dirname(__FILE__).'/../');
include 'vendor/autoload.php';
// Instead of including conf.php, define the important stuff directly
define('ENVIRONMENT', 'test');

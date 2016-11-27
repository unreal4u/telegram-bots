<?php

declare(strict_types = 1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use unreal4u\TelegramBots\DatabaseWrapper;

$logger = new Logger('TGBot');
$streamHandler = new StreamHandler('telegramApiLogs/cli-config.log');
$logger->pushHandler($streamHandler);

$wrapper = new DatabaseWrapper($logger);
return ConsoleRunner::createHelperSet($wrapper->getEntity('UptimeMonitorBot'));

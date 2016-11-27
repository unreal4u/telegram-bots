<?php

declare(strict_types = 1);

include('../src/common.php');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use unreal4u\TelegramBots\RequestHandler;

$logger = new Logger('TGBot');
$streamHandler = new StreamHandler('telegramApiLogs/main.log');
$logger->pushHandler($streamHandler);

new RequestHandler($logger, trim($_SERVER['REQUEST_URI']));

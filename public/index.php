<?php

declare(strict_types = 1);

include('../src/common.php');

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use unreal4u\TelegramBots\RequestHandler;

$logger = new Logger('TGBot');
$streamHandler = new RotatingFileHandler('telegramApiLogs/main.log', 365, Logger::DEBUG);
$logger->pushHandler($streamHandler);

$trimmedRequestUri = trim($_SERVER['DOCUMENT_URI'], '/');

new RequestHandler($logger, $trimmedRequestUri);

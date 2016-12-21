<?php

declare(strict_types = 1);

include('../src/common.php');

use GuzzleHttp\Client;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use unreal4u\MonologHandler;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramBots\RequestHandler;

$logger = new Logger('TGBot');
$httpClient = new Client();
$streamHandler = new RotatingFileHandler('telegramApiLogs/main.log', 365, Logger::DEBUG);
$monologTgLogger = new MonologHandler(new TgLog(MONOLOG_BOT, null, $httpClient), MONOLOG_CHATID, Logger::WARNING);
$logger->pushHandler($streamHandler);
$logger->pushHandler($monologTgLogger);

$trimmedRequestUri = trim($_SERVER['DOCUMENT_URI'], '/');

new RequestHandler($logger, $trimmedRequestUri, $httpClient);

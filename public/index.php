<?php

declare(strict_types = 1);

include('../src/common.php');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;

$logger = new Logger('TGBot');
$streamHandler = new StreamHandler('telegramApiLogs/main.log');
#$streamHandler->setLevel(Logger::INFO);
$logger->pushHandler($streamHandler);

$logger->addDebug('--------------------------------');
$parsedRequestUri = trim($_SERVER['REQUEST_URI'], '/');
if (array_key_exists($parsedRequestUri, BOT_TOKENS)) {
    $currentBot = BOT_TOKENS[$parsedRequestUri];

    $logger->info(sprintf('New request on bot %s, relaying to telegramApiLogs/%s.log', $currentBot, $currentBot));

    $logger = new Logger($currentBot);
    $streamHandler = new StreamHandler('telegramApiLogs/'.$currentBot.'.log');
    $logger->pushHandler($streamHandler);

    $rest_json = file_get_contents("php://input");
    $_POST = json_decode($rest_json, true);

    try {
        $completeName = 'unreal4u\\TelegramBots\\Bots\\' . $currentBot;
        /** @var $bot \unreal4u\TelegramBots\Bots\Base */
        $bot = new $completeName($logger, $parsedRequestUri);
        $logger->debug('Incoming data', [$_POST]);
        $response = $bot->createAnswer($_POST);
        // Don't perform the actual request back to telegram if the method is GetMe
        if (!($response instanceof GetMe)) {
            $bot->sendResponse($response);
        }
    } catch (\Exception $e) {
        $logger->addError(sprintf('Captured exception: "%s"', $e->getMessage()));
    }
} else {
    // Example: /uptimeBot/s3cr3turl/?monitorID=778368750&monitorURL=http%3A%2F%2Fblabla.unreal4u.com&monitorFriendlyName=ur%20checker&alertType=1&alertTypeFriendlyName=Down&alertDetails=HTTP%20500%20-%20Internal%20Server%20Error&monitorAlertContacts=2382340%3B5%3Bhttps%3A%2F%2Ftelegram.unreal4u.com%2FuptimeBot%2Fs3cr3turl%2F%3F&alertDateTime=1480215246
    $logger->debug('Received inbound url', [$_SERVER, $_GET]);
    header('Location: https://github.com/unreal4u?tab=repositories', true, 302);
}


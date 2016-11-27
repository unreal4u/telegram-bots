<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramBots\Bots\UptimeMonitorBot;

class RequestHandler {
    /**
     * @var Logger
     */
    private $logger = null;

    /**
     * @var Logger
     */
    private $botLogger = null;

    public function __construct(Logger $logger, string $requestUri)
    {
        $this->logger = $logger;

        if (!array_key_exists($requestUri, BOT_TOKENS)) {
            // Can be a request from UptimeMonitor or somebody scanning us
            $this->logger->debug('Received inbound url not coming from Telegram servers');
            $this->uptimeMonitorNotification($requestUri);
        } else {
            // Can only be a request directly to out bot from Telegram servers
            $this->newBotRequest(BOT_TOKENS[$requestUri], $requestUri);
        }
    }

    private function setupBotLogger(string $currentBot): RequestHandler
    {
        $this->botLogger = new Logger($currentBot);
        $streamHandler = new StreamHandler('telegramApiLogs/'.$currentBot.'.log');
        $this->botLogger->pushHandler($streamHandler);

        return $this;
    }

    private function newBotRequest(string $currentBot, string $botToken): bool
    {
        $this->logger->info(sprintf(
            'New request on bot %s, logging to telegramApiLogs/%s.log',
            $currentBot,
            $currentBot
        ));

        $this->setupBotLogger($currentBot);

        $rest_json = file_get_contents("php://input");
        $_POST = json_decode($rest_json, true);

        try {
            $completeName = 'unreal4u\\TelegramBots\\Bots\\' . $currentBot;
            /** @var $bot \unreal4u\TelegramBots\Bots\Base */
            $bot = new $completeName($this->botLogger, $botToken);
            $this->botLogger->debug('Incoming data', [$_POST]);
            $bot->createAnswer($_POST);
            $bot->sendResponse();
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Captured exception: "%s" for bot %s', $e->getMessage(), $currentBot));
        }

        return true;
    }

    /**
     * @param string $requestUri
     * @return bool
     */
    private function uptimeMonitorNotification(string $requestUri): bool
    {
        $redirect = true;
        $requestUriParts = explode('/', $requestUri);

        if (strtolower($requestUriParts[1]) === 'uptimemonitorbot') {
            $this->setupBotLogger('UptimeMonitorBot');
            $flippedKeys = array_flip(BOT_TOKENS);

            try {
                $bot = new UptimeMonitorBot($this->botLogger, $flippedKeys['UptimeMonitorBot']);
                $eventManager = $bot->handleUptimeMonitorNotification($_GET, $requestUriParts[2]);
                /** @var Message $message */
                $message = $bot->sendResponse();
                $redirect = false;
                if ($message->message_id !== false) {
                    // To be able to respond with a reply, quoting the original text
                    $eventManager->setEventNotified($message->message_id);
                }

            } catch (\Exception $e) {
                // Do nothing here and let the requestHandler redirect the user to my github page
            }
        }

        if ($redirect === true) {
            $this->logger->warning('Request not coming from uptime monitor or Telegram servers, please check logs');
            header('Location: https://github.com/unreal4u?tab=repositories', true, 302);
        }

        return true;
    }
}

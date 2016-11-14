<?php

namespace unreal4u\TelegramBots\Bots;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramBots\Bots\Interfaces\Bots;

abstract class BotsImplementation implements Bots {
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var Client
     */
    protected $HTTPClient = null;

    /**
     * @var string
     */
    protected $token = '';

    /**
     * Handy shortcut
     * @var int
     */
    protected $userId = 0;

    /**
     * Handy shortcut
     * @var int
     */
    protected $chatId = 0;

    /**
     * Handy shortcut
     * @var string
     */
    protected $action = '';

    /**
     * @var Update
     */
    protected $updateObject = null;

    final public function __construct(LoggerInterface $logger, string $token, Client $client = null)
    {
        $this->logger = $logger;
        $this->token = $token;
        $this->HTTPClient = $client;
    }

    final protected function extractBasicInformation(array $postData): BotsImplementation
    {
        $update = new Update($postData);
        $this->userId = $update->message->from->id;
        $this->chatId = $update->message->chat->id;
        $this->action = $update->message->text;

        return $this;
    }
}

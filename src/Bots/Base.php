<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramBots\Bots\Interfaces\Bots;
use unreal4u\TelegramBots\Models\Entities\Monitors;

abstract class Base implements Bots {
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
     * Handy shortcut
     * @var string
     */
    protected $subArguments = '';

    /**
     * @var Update
     */
    protected $updateObject = null;

    /**
     * @var Monitors
     */
    protected $monitor = null;

    final public function __construct(LoggerInterface $logger, string $token, Client $client = null)
    {
        $this->logger = $logger;
        $this->token = $token;
        // If no client provided, create a new instance of a client
        if (is_null($client)) {
            $client = new Client();
        }

        $this->HTTPClient = $client;
    }

    /**
     * Explodes the Update object into an actual object and sets some basic information
     *
     * @param array $postData
     * @return Base
     */
    final protected function extractBasicInformation(array $postData): Base
    {
        $this->updateObject = new Update($postData);
        $this->userId = $this->updateObject->message->from->id;
        $this->chatId = $this->updateObject->message->chat->id;

        $messageText = $this->updateObject->message->text;

        $this->action = substr($messageText, 1);
        $extraArguments = strpos($this->action, ' ');
        if ($extraArguments !== false) {
            $this->subArguments = substr($this->action, $extraArguments);
            $this->action = substr($this->action, 0, $extraArguments);
        }

        return $this;
    }

    /**
     * Sends a response back to the Telegram servers
     *
     * @param TelegramMethods $message
     * @return TelegramTypes
     */
    final public function sendResponseBack(TelegramMethods $message): TelegramTypes
    {
        $tgLog = new TgLog($this->token, $this->logger, $this->HTTPClient);
        return $tgLog->performApiRequest($message);
    }
}

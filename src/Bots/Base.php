<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Custom\ResultNull;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramBots\Bots\Interfaces\Bots;
use unreal4u\TelegramBots\DatabaseWrapper;

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
    protected $botCommand = '';

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
     * @var EntityManager
     */
    protected $db = null;

    /**
     * @var SendMessage
     */
    protected $response = null;

    /**
     * @var Message
     */
    private $message = null;

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

        $this
            ->extractUserInformation()
            ->extractBotCommand()
            ->createMessageStub()
        ;

        return $this;
    }

    final private function extractUserInformation(): Base
    {
        if (!empty($this->updateObject->message)) {
            $this->message = $this->updateObject->message;
            $this->entities = $this->updateObject->message->entities;
        }

        if (!empty($this->updateObject->edited_message)) {
            $this->message = $this->updateObject->edited_message;
            $this->entities = $this->updateObject->edited_message->entities;
        }

        if (!empty($this->updateObject->callback_query->message)) {
            $this->message = $this->updateObject->callback_query->message;
            $this->entities = $this->updateObject->callback_query->message->entities;
        }

        if (!empty($this->message)) {
            $this->chatId = $this->message->chat->id;
            $this->userId = $this->message->from->id;
        } else {
            throw new \Exception('Impossible condition detected or faulty update message...');
        }

        return $this;
    }

    /**
     * Will create a SendMessage stub in response
     *
     * @return Base
     */
    final private function createMessageStub(): Base
    {
        $this->response = new SendMessage();
        $this->response->chat_id = $this->chatId;
        $this->response->reply_to_message_id = $this->message->message_id;
        $this->response->parse_mode = 'Markdown';
        // Send short, concise messages without interference of links
        $this->response->disable_web_page_preview = true;

        return $this;
    }

    /**
     * Sends the generated response back to the Telegram servers
     *
     * @return TelegramTypes
     */
    final public function sendResponse(): TelegramTypes
    {
        if (!($this->response instanceof GetMe)) {
            $tgLog = new TgLog($this->token, $this->logger, $this->HTTPClient);
            return $tgLog->performApiRequest($this->response);
        }

        return new ResultNull();
    }

    /**
     * Sets up the database settings for the current bot
     *
     * @param string $entityNamespace
     * @return Base
     */
    final protected function setupDatabaseSettings(string $entityNamespace): Base
    {
        if (is_null($this->db)) {
            $wrapper = new DatabaseWrapper($this->logger);
            $this->db = $wrapper->getEntity($entityNamespace);
        }

        return $this;
    }

    /**
     * Extracts the bot command from the update query
     *
     * @return Base
     */
    final private function extractBotCommand(): Base
    {
        foreach ($this->message->entities as $entity) {
            if ($entity->type == 'bot_command') {
                $this->botCommand = substr($this->message->text, $entity->offset + 1, $entity->length);
                // Multiple bots in one group can be called with `/start@NameOfTheBot`, so strip the name of the bot
                if (strpos($this->botCommand, '@') !== false) {
                    $this->botCommand = substr($this->botCommand, 0, strpos($this->botCommand, '@'));
                }

                $this->subArguments = substr($this->message->text, $entity->offset + $entity->length + 1);
            }
        }

        return $this;
    }
}

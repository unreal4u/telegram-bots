<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\CallbackQuery;
use unreal4u\TelegramAPI\Telegram\Types\Custom\ResultNull;
use unreal4u\TelegramAPI\Telegram\Types\Inline\ChosenResult;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Query;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramBots\Bots\Interfaces\Bots;
use unreal4u\TelegramBots\DatabaseWrapper;
use unreal4u\TelegramBots\Exceptions\InvalidUpdateObject;

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
    protected $message = null;

    /**
     * @var MessageEntity
     */
    protected $entities = null;

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
        $this->decomposeUpdateObject();

        return $this;
    }

    /**
     * Decomposes the incoming update object into subparts we can actually use
     *
     * At most one of the optional parameters can be present in any given update
     * @see https://core.telegram.org/bots/api#update
     *
     * Where optional parameters is one of the following:
     * message: New incoming message of any kind — text, photo, sticker, etc.
     * edited_message: New version of a message that is known to the bot and was edited
     * channel_post: New incoming channel post of any kind — text, photo, sticker, etc.
     * edited_channel_post: New version of a channel post that is known to the bot and was edited
     * inline_query: New incoming inline query
     * chosen_inline_result: The result of an inline query that was chosen by a user and sent to their chat partner.
     * callback_query: New incoming callback query
     *
     * @throws InvalidUpdateObject
     * @throws \Exception
     * @return Base
     */
    final private function decomposeUpdateObject(): Base
    {
        foreach ($this->updateObject as $telegramTypeName => $telegramType) {
            if ($telegramType instanceof Query || $telegramType instanceof ChosenResult) {
                // TODO There are a lot of things to do for this kind of messages, but no examples at hand right now
                $this->userId = $telegramType->from->id;
                throw new \Exception('To be implemented...');
            } elseif ($telegramType instanceof CallbackQuery) {
                // A callback query can also originate from a inline bot result, in that case, Message isn't set
                $this->userId = $telegramType->from->id;
                if (!empty($telegramType->message)) {
                    $this->message = $telegramType->message;
                    $this->entities = $telegramType->message->entities;
                    $this->chatId = $telegramType->message->chat->id;
                }
                // Once we have the basic values we need to continue, break out of the loop
                break;
            } elseif (is_object($telegramType)) {
                $this->message = $telegramType;
                $this->entities = $telegramType->entities;
                $this->chatId = $telegramType->chat->id;
                $this->userId = $telegramType->from->id;
                $this->extractBotCommand();
                // Once we have the basic values we need to continue, break out of the loop
                break;
            } else {
                if (!empty($telegramType) && $telegramTypeName !== 'update_id') {
                    $this->logger->critical('Faulty Update object detected! Check ASAP', [
                        'typeObject' => $telegramTypeName,
                        'update' => $this->updateObject,
                    ]);
                    throw new InvalidUpdateObject('Impossible condition detected or faulty update message...');
                }
            }
        }

        return $this;
    }

    /**
     * Will create a SendMessage stub in response
     *
     * @return Base
     */
    final protected function createSimpleMessageStub(): Base
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

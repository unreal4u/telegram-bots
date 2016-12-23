<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\CallbackQuery;
use unreal4u\TelegramAPI\Telegram\Types\Custom\ResultNull;
use unreal4u\TelegramAPI\Telegram\Types\Inline\ChosenResult;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Query;
use unreal4u\TelegramAPI\Telegram\Types\Message;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramBots\Bots\Interfaces\Bots;
use unreal4u\TelegramBots\DatabaseWrapper;
use unreal4u\TelegramBots\Exceptions\InvalidUpdateObject;

abstract class Base implements Bots
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    protected $httpClient;

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
     * Arguments passed on to the request. Can contain malicious data, so act with caution!
     * @var array
     */
    protected $subArguments = [];

    /**
     * @var Update
     */
    protected $updateObject;

    /**
     * @var EntityManager
     */
    protected $db;

    /**
     * @var TelegramMethods
     */
    protected $response;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var MessageEntity
     */
    protected $entities;

    final public function __construct(
        LoggerInterface $logger,
        string $token,
        Client $client = null,
        EntityManager $db = null
    ) {
        $this->token = $token;
        // If no client provided, create a new instance of a client
        if ($client === null) {
            $client = new Client();
        }

        $this->httpClient = $client;
        $this->db = $db;
        $this->logger = $logger;
        $this->logger->debug('Finished constructing bot');
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
     * Assigns the basic stuff if the incoming Update object refers to a inline query
     *
     * @param TelegramTypes $telegramType
     * @return Base
     */
    final private function handleSpecialCases(TelegramTypes $telegramType): Base
    {
        // TODO Complete development of these kind of messages
        /** @var ChosenResult $telegramType */
        $this->userId = $telegramType->from->id;
        return $this;
    }

    /**
     * Assigns the basic stuff if the incoming Update object refers to a CallbackQuery
     *
     * This is quite tricky because we use the specific data fields to know in which part of the process we actually
     * are, but this data can't be trusted and therefor we must manually check it.
     *
     * @param CallbackQuery $telegramType
     * @return Base
     */
    final private function handleCallbackQuery(CallbackQuery $telegramType): Base
    {
        $this->userId = $telegramType->from->id;
        // A callback query can also originate from a inline bot result, in that case, Message isn't set
        if (!empty($telegramType->message)) {
            $this->message = $telegramType->message;
            $this->entities = $telegramType->message->entities;
            $this->chatId = $telegramType->message->chat->id;
        }

        /*
         * In the case of our bots, we'll always send some parameters with data, the first of which is the botCommand
         * and the second will always be some subArguments.
         *
         * Please note that this data isn't safe, as it can be manipulated by the UA
         * @see https://core.telegram.org/bots/api#callbackquery
         */
        if (!empty($telegramType->data)) {
            $parsedUrl = parse_url($telegramType->data);
            if (in_array($parsedUrl['path'], $this->validSubcommands())) {
                $this->botCommand = $parsedUrl['path'];
                parse_str($parsedUrl['query'], $this->subArguments);
            }
        }
        // Once we have the basic values we need to continue, break out of the loop
        return $this;
    }

    /**
     * Assigns the basic stuff if the incoming Update object refers to a Message
     *
     * @param Message $telegramType
     * @return Base
     */
    final private function handleMessageObject(Message $telegramType): Base
    {
        $this->message = $telegramType;
        $this->entities = $telegramType->entities;
        $this->chatId = $telegramType->chat->id;
        $this->userId = $telegramType->from->id;
        // We are now ready to get to know what the actual sent command was
        $this->extractBotCommand();

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
                $this->handleSpecialCases($telegramType);
                throw new \Exception('To be implemented...');
                // Once we have the basic values we need to continue, break out of the loop ASAP
                break;
            } elseif ($telegramType instanceof CallbackQuery) {
                $this->handleCallbackQuery($telegramType);
                // Once we have the basic values we need to continue, break out of the loop ASAP
                break;
            } elseif ($telegramType instanceof Message) {
                $this->handleMessageObject($telegramType);

                // Handle special cases: adding or removing a chat member
                if ($telegramType->new_chat_member instanceof User) {
                    $this->handleChatMemberUpdate($telegramType->new_chat_member, 'start');
                }

                if ($telegramType->left_chat_member instanceof User) {
                    $this->handleChatMemberUpdate($telegramType->left_chat_member, '/end');
                }
                // Once we have the basic values we need to continue, break out of the loop ASAP
                break;
            } else {
                if (!empty($telegramType) && $telegramTypeName !== 'update_id') {
                    $this->logger->critical('Faulty Update object detected! Check ASAP', [
                        'typeObject' => $telegramTypeName,
                        'update' => $this->updateObject,
                    ]);
                    throw new InvalidUpdateObject('Impossible condition detected or faulty update message...');
                }
                // Exception to the rule: do NOT break out of the loop and check the rest
            }
        }

        return $this;
    }

    /**
     * Gets the current bot information directly from the Telegram servers
     *
     * @return User
     */
    final private function getMe(): User
    {
        $this->logger->info('Requesting a live GetMe() method');
        $tgLog = new TgLog($this->token, $this->logger, $this->httpClient);
        return $tgLog->performApiRequest(new GetMe());
    }

    /**
     * Handles off operations concerning adding or leaving chat members
     *
     * @param User $user
     * @param string $operation
     * @return Base
     */
    final private function handleChatMemberUpdate(User $user, string $operation): Base
    {
        $this->logger->debug('Checking whether added or eliminated member is the bot itself');
        if ($this->getMe()->id === $user->id) {
            $this->botCommand = $operation;
            $this->logger->info('Bot is the member being added/removed to a group chat', ['operation' => $operation]);
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
        $this->logger->debug('Creating simple message stub with some default values');
        $this->response = new SendMessage();
        $this->response->chat_id = $this->chatId;
        $this->response->reply_to_message_id = $this->message->message_id;
        $this->response->parse_mode = 'Markdown';
        // Send short, concise messages without interference of links
        $this->response->disable_web_page_preview = true;

        return $this;
    }

    /**
     * Creates an EditMessageText object instead of a SendMessage
     *
     * @return UptimeMonitorBot
     */
    final protected function createEditableMessage(): Base
    {
        $this->logger->debug('Creating new editable message object');
        $this->response = new EditMessageText();
        $this->response->message_id = $this->message->message_id;
        $this->response->chat_id = $this->chatId;

        return $this;
    }

    /**
     * Sends the generated response back to the Telegram servers
     *
     * @return TelegramTypes
     */
    final public function sendResponse(): TelegramTypes
    {
        $this->logger->debug('Sending response back', ['instanceType' => get_class($this->response)]);
        if ($this->response !== null && !($this->response instanceof GetMe)) {
            $tgLog = new TgLog($this->token, $this->logger, $this->httpClient);
            return $tgLog->performApiRequest($this->response);
        }
        $this->logger->debug('Nothing to send back, finishing up');

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
            $this->logger->debug('Initializing database connection', ['entityNamespace' => $entityNamespace]);
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
        // The entities will contain information about the sent botCommand, so check that
        foreach ($this->message->entities as $entity) {
            if ($entity->type == 'bot_command') {
                $this->botCommand = substr($this->message->text, $entity->offset + 1, $entity->length);
                // Multiple bots in one group can be called with `/start@NameOfTheBot`, so strip the name of the bot
                if (strpos($this->botCommand, '@') !== false) {
                    $this->botCommand = substr($this->botCommand, 0, strpos($this->botCommand, '@'));
                }
                $this->logger->debug('Found a bot_command within the entities', ['command' => $this->botCommand]);

                $this->subArguments[] = substr($this->message->text, $entity->offset + $entity->length + 1);
            }
        }

        return $this;
    }
}

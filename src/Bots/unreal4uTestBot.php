<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

class unreal4uTestBot extends Base {
    /**
     * @param array $postData
     * @return TelegramMethods
     */
    public function createAnswer(array $postData=[]): TelegramMethods
    {
        $this->extractBasicInformation($postData);

        if ($this->userId !== UNREAL4U_ID) {
            return $this->invalidUser();
        }

        // Default: a simple message
        $this->createSimpleMessageStub();
        switch ($this->botCommand) {
            case 'start':
                return $this->start();
                break;
            case '/end':
                return new GetMe();
            case 'help':
                return $this->help();
                break;
            default:
                return new GetMe();
                break;
        }
    }

    protected function invalidUser(): SendMessage
    {
        $this->createSimpleMessageStub();
        $this->response->text = 'This bot is intended to be used for internal development only';
        $this->logger->error('Unauthorized access to bot', ['userId' => $this->userId, 'chatId' => $this->chatId]);

        return $this->response;
    }

    /**
     * Action to execute when botCommand is set
     * @return SendMessage
     */
    protected function start(): SendMessage
    {
        $this->logger->debug('[CMD] Inside START');
        $this->response->text = sprintf(
            _('Welcome! Consult `/help` at any time to get a list of command and options.')
        );

        // Complete with the text from the help page
        $this->help();

        return $this->response;
    }

    /**
     * Action to execute when botCommand is set
     * @return SendMessage
     */
    protected function help(): SendMessage
    {
        $this->logger->debug('[CMD] Inside HELP');
        $messageText  = '*Example commands:*'.PHP_EOL;

        $this->response->text .= $messageText;
        return $this->response;
    }

    protected function informAboutEmptyCommand(): SendMessage
    {
        $this->createSimpleMessageStub();
        $this->logger->warning('Empty botcommand detected');
        $this->response->text = 'Sorry but I don\'t understand this option, please check `/help`';

        return $this->response;
    }

    /**
     * Returns an array with valid subcommands for the bot
     * @return array
     */
    public function validSubcommands(): array
    {
        return [
            'start',
            'help',
        ];
    }
}

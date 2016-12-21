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

        switch ($this->botCommand) {
            case 'start':
                $this->createSimpleMessageStub();
                return $this->start();
                break;
            case '/end':
                return new GetMe();
            case 'help':
                $this->createSimpleMessageStub();
                return $this->help();
                break;
            case '':
                $this->logger->debug('Sent data was the following', [$this->message]);
                return $this->checkForCities();
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
            _('Welcome! Consult /help at any time to get a list of command and options.')
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
        $messageText .= '- `/get_time_for_timezone America/Santiago` -> Displays the current time in America/Santiago'.PHP_EOL;
        $messageText .= '- `America/Santiago` -> Displays the current time in America/Santiago'.PHP_EOL;
        $messageText .= '- `London` -> Will display a selection for which London you actually mean'.PHP_EOL;
        //$messageText .= '`/set_display_format en-US` -> Sets the display format, use a valid locale'.PHP_EOL;
        $messageText .= '- You can also send a location (Works from phone only)';

        $this->response->text .= $messageText;
        return $this->response;
    }

    private function checkForCities(): SendMessage
    {
        $this->response->text = 'The input was: '.$this->message->text;

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
            'setup',
            'get_notify_url',
            'regenerate_notify_url',
            'help',
        ];
    }
}

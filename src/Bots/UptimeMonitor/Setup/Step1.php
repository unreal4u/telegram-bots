<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class Step1 extends Common {
    public function createAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            'Welcome! Let\'s get you up and running. [Open up this url](%s) and create a free account%s%s',
            'https://uptimerobot.com',
            PHP_EOL.PHP_EOL,
            'Have you created the account?'
        );

        $inlineKeyboardButton = new Button();
        $inlineKeyboardButton->text = 'Yes, take me to the next step!';
        $inlineKeyboardButton->callback_data = '/setup/step2';
        $this->logger->debug('Created inlineKeyboardButton');

        $inlineKeyboardMarkup = new Markup();
        $inlineKeyboardMarkup->inline_keyboard[] = [$inlineKeyboardButton];
        $this->logger->debug('Created inlineKeyboardMarkup');

        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';
        $this->response->reply_markup = $inlineKeyboardMarkup;
        $this->logger->debug('Response ready');

        return $this->response;
    }
}

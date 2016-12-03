<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class Step3 extends Common {
    public function createAnswer(): TelegramMethods
    {
        /** @var EditMessageText $this->response */
        $this->response->text = sprintf(
            'Great! Now you can add a monitor. Click on the big green button that says "Add New Monitor".%s%s',
            PHP_EOL.PHP_EOL,
            'Have you created a monitor?'
        );

        $inlineKeyboardButton = new Button();
        $inlineKeyboardButton->text = 'Yes, take me to the next step!';
        $inlineKeyboardButton->callback_data = 'setup?step=2';
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

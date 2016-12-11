<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Common;

class Confirmation extends Common {
    public function generateAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            '%s%s%s',
            'This will invalidate all your current Monitors! Please note that you have to edit the notify URL in ',
            'https://uptimerobot.com/ as well!',
            PHP_EOL.'Are you sure you want to continue?'
        );

        $inlineKeyboardAcceptButton = new Button();
        $inlineKeyboardAcceptButton->text = 'Yes';
        $inlineKeyboardAcceptButton->callback_data = 'regenerate_notify_url?step=regenerate';
        $this->logger->debug('Created inlineKeyboardAcceptButton');

        $inlineKeyboardRejectButton = new Button();
        $inlineKeyboardRejectButton->text = 'No';
        $inlineKeyboardRejectButton->callback_data = 'regenerate_notify_url?step=cancel';
        $this->logger->debug('Created inlineKeyboardRejectButton');

        $inlineKeyboardMarkup = new Markup();
        $inlineKeyboardMarkup->inline_keyboard[] = [$inlineKeyboardAcceptButton, $inlineKeyboardRejectButton];
        $this->logger->debug('Created inlineKeyboardMarkup');

        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';
        $this->response->reply_markup = $inlineKeyboardMarkup;

        $this->logger->debug('Response ready');
        return $this->response;
    }
}

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class Step3 extends Common {
    public function generateAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            'And that would be all! You\'ll receive any notifications on the channel where you configured the '.
            'notification URL'
        );

        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';
        $this->logger->debug('Response ready');

        return $this->response;
    }
}

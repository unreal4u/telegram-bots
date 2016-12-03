<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class Step2 extends Common {
    /**
     * @var string
     */
    private $notifyUrl = '';

    public function setNotifyUrl(string $notifyUrl): Step2
    {
        $this->notifyUrl = $notifyUrl;
        return $this;
    }

    public function createAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            'Great! Now you can go to "My Settings" and add an alert contact. Select a "Web-Hook" type and insert the '.
            'following URL into "URL to Notify": `%s`%s%s',
            $this->notifyUrl,
            PHP_EOL.PHP_EOL,
            'Have you created the alert contact?'
        );

        $inlineKeyboardButton = new Button();
        $inlineKeyboardButton->text = 'Yes, take me to the next step!';
        $inlineKeyboardButton->callback_data = 'setup?step=3';
        $this->logger->debug('Created inlineKeyboardButton');

        $inlineKeyboardBackButton = new Button();
        $inlineKeyboardBackButton->text = 'Back to step 1';
        $inlineKeyboardBackButton->callback_data = 'setup';
        $this->logger->debug('Created inlineKeyboardBackButton');

        $inlineKeyboardMarkup = new Markup();
        $inlineKeyboardMarkup->inline_keyboard[] = [$inlineKeyboardButton, $inlineKeyboardBackButton];
        $this->logger->debug('Created inlineKeyboardMarkup configuration');

        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';
        $this->response->reply_markup = $inlineKeyboardMarkup;
        $this->logger->debug('Response ready');

        return $this->response;
    }
}

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class Step2 extends Common {
    /**
     * @var string
     */
    private $notifyUrl = '';

    /**
     * @param string $notifyUrl
     * @return Step2
     */
    public function setNotifyUrl(string $notifyUrl): Step2
    {
        $this->notifyUrl = $notifyUrl;
        return $this;
    }

    /**
     * @return SendPhoto
     */
    public function generatePhotoAnswer(): SendPhoto
    {
        $this->response->caption = sprintf(
            '%s%s%s%s%s',
            '• Click on "My Settings"'.PHP_EOL,
            '• Scroll midway and click on "Add Alert Contact"'.PHP_EOL,
            '• Select "Web-Hook"'.PHP_EOL,
            '• Insert above URL in "URL to Notify"'.PHP_EOL,
            '• Ensure "Send as JSON" is unchecked'.PHP_EOL
        );
        $this->response->photo = new InputFile('media/add-monitor.png');
        $this->response->reply_markup = $this->getInlineKeyboardMarkup(false);

        return $this->response;
    }

    public function generateAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            'Great! Now you can go to "My Settings" and then "Add Alert Contact". Select a "Web-Hook" type and insert '.
            'the following URL into "URL to Notify", include the question mark at the end and ensure that the option '.
            '"Send as JSON (application/json)" is **unchecked**: `%s`. Finally, click on "Create Alert Contact".%s%s',
            $this->notifyUrl,
            PHP_EOL.PHP_EOL,
            'Have you created the alert contact?'
        );

        // Show the picture must not be shown when displaying the picture, only when displaying the text only version
        $inlineKeyboardShowPicButton = new Button();
        $inlineKeyboardShowPicButton->text = 'Just show me a picture';
        $inlineKeyboardShowPicButton->callback_data = 'setup?step=2-picture';
        $this->logger->debug('Created inlineKeyboardShowPicButton');

        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';

        $inlineKeyboardMarkup = $this->getInlineKeyboardMarkup();
        $inlineKeyboardMarkup->inline_keyboard[] = [$inlineKeyboardShowPicButton];
        $this->response->reply_markup = $inlineKeyboardMarkup;
        $this->logger->debug('Response ready');

        return $this->response;
    }

    /**
     * @return Markup
     */
    private function getInlineKeyboardMarkup(bool $createEditableMessage = true): Markup
    {
        $inlineKeyboardButton = new Button();
        $inlineKeyboardButton->text = 'Yes, next step!';
        $inlineKeyboardButton->callback_data = 'setup?step=3'.empty($createEditableMessage) ? '&newMsg=true' : '';
        $this->logger->debug('Created inlineKeyboardButton');

        $inlineKeyboardBackButton = new Button();
        $inlineKeyboardBackButton->text = 'Back to step 1';
        $inlineKeyboardBackButton->callback_data = 'setup?step=1'.empty($createEditableMessage) ? '&newMsg=true' : '';
        $this->logger->debug('Created inlineKeyboardBackButton');

        $inlineKeyboardMarkup = new Markup();
        $inlineKeyboardMarkup->inline_keyboard[] = [$inlineKeyboardButton];
        $inlineKeyboardMarkup->inline_keyboard[] = [$inlineKeyboardBackButton];

        $this->logger->debug('Created inlineKeyboardMarkup configuration');
        return $inlineKeyboardMarkup;
    }
}

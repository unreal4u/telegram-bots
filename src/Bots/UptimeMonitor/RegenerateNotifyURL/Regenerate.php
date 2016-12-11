<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Common;
use unreal4u\TelegramBots\Exceptions\MissingNotifyUrl;

/**
 * Second step: user confirmed that he wants to regenerate the notify URL
 */
class Regenerate extends Common {
    private $notifyUrl = '';

    /**
     * Sets the notify URL to be send to the user
     *
     * @param string $notifyUrl
     * @return Regenerate
     */
    public function setNotifyUrl(string $notifyUrl): Regenerate
    {
        $this->notifyUrl = $notifyUrl;
        return $this;
    }

    /**
     * @return TelegramMethods
     * @throws MissingNotifyUrl
     */
    public function generateAnswer(): TelegramMethods
    {
        if ($this->notifyUrl === '') {
            throw new MissingNotifyUrl('No notify URL has been detected in '.__CLASS__.':'.__FUNCTION__);
        }

        $this->response->text = sprintf(
            'Your new notification URL is `%s`%s',
            $this->notifyUrl,
            PHP_EOL.'Please set this new URL in your uptimerobot.com environment, including the question mark'
        );

        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';

        $this->logger->debug('Response ready');
        return $this->response;
    }
}

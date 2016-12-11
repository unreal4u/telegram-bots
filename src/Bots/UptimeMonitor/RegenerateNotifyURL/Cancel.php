<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Common;

/**
 * Second step: user confirmed that he wants to regenerate the Notification URL
 * @package unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl
 */
class Cancel extends Common {
    public function generateAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            'Your request to regenerate the notification URL has been cancelled, everything is safe!'
        );

        $this->logger->debug('Response ready');
        return $this->response;
    }
}

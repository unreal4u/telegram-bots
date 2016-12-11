<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Common;

class Step3 extends Common {
    public function generateAnswer(): TelegramMethods
    {
        $this->response->text = sprintf(
            'Now you just have to add in each monitor the recently created Contact. This can be done by going to one '.
            'of the monitors, and selecting the recently created web-hook as a contact.%sYou will now receive any '.
            'notifications on the channel where you configured the notification URL, enjoy!',
            PHP_EOL
        );
        $this->response->disable_web_page_preview = true;
        $this->response->parse_mode = 'Markdown';

        $this->logger->debug('Response ready');
        return $this->response;
    }
}

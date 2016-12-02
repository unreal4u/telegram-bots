<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;

class Step2 extends Common {
    public function createAnswer(): TelegramMethods
    {

        return new GetMe();
    }
}

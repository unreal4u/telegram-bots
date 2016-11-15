<?php

namespace unreal4u\TelegramBots\Bots;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

class UptimeMonitorBot extends Base {
    public function run(array $postData=[]): TelegramMethods
    {
        $this
            ->extractBasicInformation($postData)
        ;

        $sendMessage = new SendMessage();
        return $sendMessage;
    }
}

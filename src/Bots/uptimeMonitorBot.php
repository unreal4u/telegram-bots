<?php

namespace unreal4u\TelegramBots\Bots;

use unreal4u\TelegramBots\Bots\Interfaces\Bots;

class UptimeMonitorBot extends BotsImplementation {
    public function run(array $postData=[]): Bots
    {
        $this
            ->extractBasicInformation($postData)
        ;

        return $this;
    }
}

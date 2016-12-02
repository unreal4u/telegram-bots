<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor\Setup;

use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramBots\Bots\Interfaces\UptimeMonitorSetupSteps;

abstract class Common implements UptimeMonitorSetupSteps {
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TelegramMethods
     */
    protected $response;

    public function __construct(LoggerInterface $logger, TelegramMethods $response)
    {
        $this->logger = $logger;
        $this->response = $response;
    }
}

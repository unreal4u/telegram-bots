<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\Interfaces;

use Psr\Log\LoggerInterface;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;

interface UptimeMonitorSetupSteps
{
    /**
     * UptimeMonitorSetupSteps general constructor.
     * @param LoggerInterface $logger
     * @param TelegramMethods $response
     */
    public function __construct(LoggerInterface $logger, TelegramMethods $response);

    /**
     * Creates and returns an appropiate answer to the data that is being posted
     *
     * @return TelegramMethods
     */
    public function createAnswer(): TelegramMethods;
}

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\Interfaces;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;

interface Bots
{
    /**
     * Bots general constructor.
     * @param LoggerInterface $logger
     * @param string $token
     * @param Client|null $client
     */
    public function __construct(LoggerInterface $logger, string $token, Client $client = null);

    /**
     * Creates and returns an appropiate answer to the data that is being posted
     *
     * @param array $postData
     * @return TelegramMethods
     */
    public function createAnswer(array $postData = []): TelegramMethods;
}

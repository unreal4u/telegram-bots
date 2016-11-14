<?php

namespace unreal4u\TelegramBots\Bots\Interfaces;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

interface Bots
{
    /**
     * Bots constructor.
     * @param LoggerInterface $logger
     * @param string $token
     * @param Client|null $client
     */
    public function __construct(LoggerInterface $logger, string $token, Client $client = null);

    public function run(array $postData = []): Bots;
}

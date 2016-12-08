<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\Interfaces;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;

interface Bots
{
    /**
     * Bots general constructor.
     * @param LoggerInterface $logger
     * @param string $token
     * @param Client $client
     * @param EntityManager $db Optional EntityManager, used for tests but could also be used to batch process stuff
     */
    public function __construct(
        LoggerInterface $logger,
        string $token,
        Client $client = null,
        EntityManager $db = null
    );

    /**
     * Creates and returns an appropiate answer to the data that is being posted
     *
     * @param array $postData
     * @return TelegramMethods
     */
    public function createAnswer(array $postData = []): TelegramMethods;

    /**
     * Returns an array with valid subcommands for the bot
     * @return array
     */
    public function validSubcommands(): array;
}

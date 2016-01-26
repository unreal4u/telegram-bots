<?php

namespace unreal4u\Bots;

use Psr\Log\LoggerInterface;

interface BotsInterface
{
    public function __construct(LoggerInterface $logger, string $token);

    public function run(array $postData = []);
}

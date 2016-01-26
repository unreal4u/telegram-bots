<?php

namespace unreal4u\Bots;

class TheTimeBot implements BotsInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string
     */
    private $token;

    public function __construct(LoggerInterface $logger, string $token)
    {

        $this->logger = $logger;
        $this->token = $token;
    }

    public function run(array $postData=[])
    {

    }
}

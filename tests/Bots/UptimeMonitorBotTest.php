<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Bots;

use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramBots\Bots\UptimeMonitorBot;
use unreal4u\TelegramBots\Tests\bootstrap;

class UptimeMonitorBotTest extends TestCase {
    /**
     * @var UptimeMonitorBot
     */
    private $wrapper = null;

    /**
     * @var bootstrap
     */
    private $bootstrap = null;

    protected function setUp()
    {
        parent::setUp();
        $this->bootstrap = new bootstrap();
        $this->bootstrap->setUpLogger();

        $this->wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456');
        $this->bootstrap->setupBot($this->wrapper);
    }

    public function testStartCommand()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('start');
        $return = $this->wrapper->run($simulatedPost);

        #var_dump($return);
    }
}

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Bots;

use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramBots\Bots\unreal4uBot;
use unreal4u\TelegramBots\Tests\bootstrap;

class unreal4uBotTest extends TestCase {
    /**
     * @var unreal4uBot
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

        $this->wrapper = new unreal4uBot($this->bootstrap->getLogger(), '123456');
        $this->bootstrap->setupBot($this->wrapper);
    }

    public function testStartCommand()
    {
        $this->markTestIncomplete('This will be implemented later on');
    }
}

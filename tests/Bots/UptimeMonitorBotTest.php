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
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf('unreal4u\\TelegramApi\\Telegram\\Methods\\SendMessage', $return);
        $this->assertStringStartsWith('Welcome to the UptimeMonitorBot', $return->text);
        $this->assertContains('The available commands are', $return->text);
        $this->assertNotEmpty($return->chat_id);
    }

    public function testHelpCommand()
    {
        $this->markTestIncomplete('TODO');
    }

    public function testSetup()
    {
        $this->markTestIncomplete('TODO');
    }

    public function testGetNotifyUrl()
    {
        $this->markTestIncomplete('TODO');
    }
}

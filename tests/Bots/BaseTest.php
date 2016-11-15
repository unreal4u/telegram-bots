<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\tests;

use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramBots\Tests\bootstrap;
use unreal4u\TelegramBots\tests\Mock\BaseMock;

class BaseTest extends TestCase
{
    /**
     * @var BaseMock
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
        $this->bootstrap->forceConfigurationSet('Base');

        $this->wrapper = new BaseMock($this->bootstrap->getLogger(), '123456');
    }

    public function testExtractBasicData()
    {
        $simulatedData = $this->bootstrap->getSimulatedPostData('start');
        $this->wrapper->testExtractBasicInformation($simulatedData);

        $this->assertSame(12345678, $this->wrapper->getUserId());
        $this->assertSame(12341234, $this->wrapper->getChatId());
        $this->assertSame('start', $this->wrapper->getAction());
        $this->assertInstanceOf('unreal4u\\TelegramAPI\\Telegram\\Types\\Update', $this->wrapper->getUpdateObject());
    }
}

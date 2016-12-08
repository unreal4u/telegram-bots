<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Bots;

use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramBots\Bots\UptimeMonitorBot;
use unreal4u\TelegramBots\Tests\bootstrap;

class UptimeMonitorBotTest extends TestCase
{
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
        $this->bootstrap
            ->setUpLogger()
            ->setupSQLiteDatabase();

        $this->wrapper = new UptimeMonitorBot(
            $this->bootstrap->getLogger(),
            '123456',
            null,
            $this->bootstrap->getEntityManager()
        );
        $this->bootstrap->setupBot($this->wrapper);
    }

    public function testStartCommand()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('start');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('Welcome to the UptimeMonitorBot', $return->text);
        $this->assertContains('The available commands are', $return->text);
        $this->assertNotEmpty($return->chat_id);
    }

    /*
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

    */

    public function testNewChatMember()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'new-chat-member');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    public function testInvalidCommand()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'invalid-command');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    /**
     * @group setup
     * @throws \Exception
     */
    public function testGroupSetup()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('setup', 'group');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(SendMessage::class, $return);
    }

    /**
     * @group setup
     */
    public function testSetupStep1()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('setup', 'step1');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(SendMessage::class, $return);
    }

    /**
     * @group setup
     * @throws \Exception
     */
    public function testSetupStep2()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('setup', 'step2');
        /** @var EditMessageText $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(EditMessageText::class, $return);
    }
}

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Bots;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramBots\Bots\UptimeMonitorBot;
use unreal4u\TelegramBots\Models\Entities\Monitors;
use unreal4u\TelegramBots\Tests\bootstrap;

class UptimeMonitorBotTest extends TestCase
{
    /**
     * @var bootstrap
     */
    private $bootstrap;

    /**
     * @var EntityManager
     */
    private $db;

    protected function setUp()
    {
        parent::setUp();

        $this->bootstrap = new bootstrap();
        $this->bootstrap
            ->setUpLogger()
            ->setupSQLiteDatabase()
            ->forceConfigurationSet('UptimeMonitorBot')
        ;
        $this->db = $this->bootstrap->getEM();
    }

    /**
     * @param Response[] $responses
     * @return Client
     */
    private function getClientMockGetMe(array $responses): Client
    {
        $mockResponses = new MockHandler($responses);
        $handler = HandlerStack::create($mockResponses);
        return new Client(['handler' => $handler]);
    }

    public function testStartCommand()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', null, $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('start');
        /** @var SendMessage $return */
        $return = $wrapper->createAnswer($simulatedPost);

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

    public function testGetNotifyUrl()
    {
        $this->markTestIncomplete('TODO');
    }

    */

    /**
     * @group AddingAndLeaving
     * @throws \Exception
     * @depends testStartCommand
     */
    public function testNewChatMember()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents('tests/commandEmulator/Bots/UptimeMonitorBot/getme.json')),
        ]), $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'new-chat-member');
        /** @var GetMe $return */
        $return = $wrapper->createAnswer($simulatedPost);
        // Confirm entry has been added for current chatId
        /** @var Monitors $monitor */
        $monitor = $this->db
            ->getRepository(Monitors::class)
            ->findOneBy(['chatId' => -12341234]);

        $this->assertSame(12345678, $monitor->getUserId());
        $this->assertInstanceOf(SendMessage::class, $return);
    }

    /**
     * @group AddingAndLeaving
     * @throws \Exception
     * @depends testNewChatMember
     */
    public function testLeaveChatMember()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents('tests/commandEmulator/Bots/UptimeMonitorBot/getme.json')),
            new Response(200, [], file_get_contents('tests/commandEmulator/Bots/UptimeMonitorBot/getme.json')),
        ]), $this->db);

        // Add entry to db
        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'new-chat-member');
        $wrapper->createAnswer($simulatedPost);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'left-chat-member');
        // Confirm entry has been added for current chatId
        /** @var Monitors $monitor */
        $monitor = $this->db
            ->getRepository(Monitors::class)
            ->findOneBy(['chatId' => -12341234]);

        $this->assertSame(12345678, $monitor->getUserId());
        $return = $wrapper->createAnswer($simulatedPost);
        // Also assert that message is not in database
        $monitor = $this->db
            ->getRepository(Monitors::class)
            ->findOneBy(['chatId' => -12341234]);

        $this->assertNull($monitor);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    /**
     * @group AddingAndLeaving
     * @throws \Exception
     */
    public function testNewChatMemberNotBot()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents('tests/commandEmulator/Bots/UptimeMonitorBot/getme.json')),
        ]), $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'new-chat-member-not-bot');
        /** @var GetMe $return */
        $return = $wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    /**
     * @group AddingAndLeaving
     * @throws \Exception
     */
    public function testLeaveChatMemberNotBot()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents('tests/commandEmulator/Bots/UptimeMonitorBot/getme.json')),
        ]), $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'left-chat-member');

        $return = $wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    public function testInvalidCommand()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', null, $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'invalid-command');
        /** @var SendMessage $return */
        $return = $wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    /**
     * @group setup
     * @throws \Exception
     */
    public function testGroupSetup()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', null, $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('setup', 'group');
        /** @var SendMessage $return */
        $return = $wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(SendMessage::class, $return);
    }

    /**
     * @group setup
     */
    public function testSetupStep1()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', null, $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('setup', 'step1');
        /** @var SendMessage $return */
        $return = $wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(SendMessage::class, $return);
    }

    /**
     * @group setup
     * @throws \Exception
     */
    public function testSetupStep2()
    {
        $wrapper = new UptimeMonitorBot($this->bootstrap->getLogger(), '123456', null, $this->db);

        $simulatedPost = $this->bootstrap->getSimulatedPostData('setup', 'step2');
        /** @var EditMessageText $return */
        $return = $wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(EditMessageText::class, $return);
    }
}

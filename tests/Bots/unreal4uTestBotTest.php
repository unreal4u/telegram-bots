<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Bots;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramBots\Bots\unreal4uTestBot;
use unreal4u\TelegramBots\Tests\bootstrap;

define('UNREAL4U_ID', 12345678);
define('GEONAMES_API_USERID', 'testuser');

class unreal4uTestBotTest extends TestCase
{
    /**
     * @var bootstrap
     */
    private $bootstrap;

    /**
     * @var EntityManager
     */
    private $db;

    /**
     * @var unreal4uTestBot
     */
    private $wrapper;

    /**
     * @var string
     */
    private $baseMock = 'tests/commandEmulator/Bots/unreal4uTestBot/';

    protected function setUp()
    {
        parent::setUp();

        $this->bootstrap = new bootstrap();
        $this->bootstrap
            ->setUpLogger()
            ->forceConfigurationSet('unreal4uTestBot')
        ;

        $this->wrapper = new unreal4uTestBot($this->bootstrap->getLogger(), '123456');
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
        $simulatedPost = $this->bootstrap->getSimulatedPostData('start');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('Welcome! Consult `/help` at any', $return->text);
        $this->assertNotEmpty($return->chat_id);
    }

    public function testInvalidCommand()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'invalid-command');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(GetMe::class, $return);
    }

    public function testSendDirectTimezone()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'AmericaSantiago');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('The date & time in *America/Santiago*', $return->text);
    }

    public function testDirectCityOneResult()
    {
        // Redefine as we must give a custom HTTP wrapper
        $this->wrapper = new unreal4uTestBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents($this->baseMock.'typing-accepted.json')),
            new Response(200, [], file_get_contents($this->baseMock.'geonames/getOneResult.json')),
            new Response(200, [], file_get_contents($this->baseMock.'geonames/eindhoven-timezone.json')),
        ]));

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'cityOneResult');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('The date & time in *Europe/Amsterdam*', $return->text);
    }

    public function testDirectCountry()
    {
        // assert country doesn't include sublvl2
        $this->assertTrue(true);
    }

    public function testSendLocation()
    {
        // Redefine as we must give a custom HTTP wrapper
        $this->wrapper = new unreal4uTestBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents($this->baseMock.'geonames/getCustomLocation.json')),
        ]));

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'sendLocation');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('The date & time in *Europe/Warsaw*', $return->text);
    }

    public function testBotCommandTimezone()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('get_time_for_timezone', 'AmericaSantiago');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('The date & time in *America/Santiago*', $return->text);
    }
}

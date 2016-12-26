<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Bots;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase as TestCase;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramBots\Bots\TheTimeBot;
use unreal4u\TelegramBots\Tests\bootstrap;

define('GEONAMES_API_USERID', 'testuser');

class TheTimeBotTest extends TestCase
{
    /**
     * @var bootstrap
     */
    private $bootstrap;

    /**
     * @var TheTimeBot
     */
    private $wrapper;

    /**
     * @var string
     */
    private $baseMock = 'tests/commandEmulator/Bots/TheTimeBot/';

    protected function setUp()
    {
        parent::setUp();

        $this->bootstrap = new bootstrap();
        $this->bootstrap
            ->setUpLogger()
            ->forceConfigurationSet('TheTimeBot')
        ;

        $this->wrapper = new TheTimeBot($this->bootstrap->getLogger(), '123456');
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
        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertContains('Sorry but I don\'t understand this option', $return->text);
    }

    /**
     * @group InvalidBotCommand
     * @throws \Exception
     */
    public function testBotCommand()
    {
        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'invalidBotCommand');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);
        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertContains('Sorry but I don\'t understand this option', $return->text);
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
        $this->wrapper = new TheTimeBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
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
        // Redefine as we must give a custom HTTP wrapper
        $this->wrapper = new TheTimeBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents($this->baseMock.'typing-accepted.json')),
            new Response(200, [], file_get_contents($this->baseMock.'geonames/search-countryAR.json')),
        ]));

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'getCountry');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('There was more than 1 result for your query', $return->text);
        $this->assertCount(6, $return->reply_markup->inline_keyboard);
        $this->assertSame('Argentine Republic, Argentina', $return->reply_markup->inline_keyboard[0][0]->text);
        $this->assertSame('Argentina, Chiapas, Mexico', $return->reply_markup->inline_keyboard[5][0]->text);
    }

    /**
     * @depends testDirectCountry
     * @throws \Exception
     */
    public function testCountryAfterSelection()
    {
        // Redefine as we must give a custom HTTP wrapper
        $this->wrapper = new TheTimeBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents($this->baseMock.'geonames/timezone-countrySelectionAR.json')),
        ]));

        $simulatedPost = $this->bootstrap->getSimulatedPostData('update', 'countrySelectionAR');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(EditMessageText::class, $return);
        $this->assertStringStartsWith('The date & time in *America/Argentina/Cordoba*', $return->text);
    }

    public function testCommandCitySearch()
    {
        // Redefine as we must give a custom HTTP wrapper
        $this->wrapper = new TheTimeBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
            new Response(200, [], file_get_contents($this->baseMock.'typing-accepted.json')),
            new Response(200, [], file_get_contents($this->baseMock.'geonames/search-citySantiago.json')),
        ]));

        $simulatedPost = $this->bootstrap->getSimulatedPostData('get', 'city');
        /** @var SendMessage $return */
        $return = $this->wrapper->createAnswer($simulatedPost);

        $this->assertInstanceOf(SendMessage::class, $return);
        $this->assertStringStartsWith('There was more than 1 result for your query', $return->text);
        $this->assertCount(6, $return->reply_markup->inline_keyboard);
        $this->assertSame('Santiago, Santiago Metropolitan, Chile', $return->reply_markup->inline_keyboard[0][0]->text);
        $this->assertSame('Santiago Ixcuintla, Nayarit, Mexico', $return->reply_markup->inline_keyboard[5][0]->text);
    }

    public function testSendLocation()
    {
        // Redefine as we must give a custom HTTP wrapper
        $this->wrapper = new TheTimeBot($this->bootstrap->getLogger(), '123456', $this->getClientMockGetMe([
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

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use unreal4u\localization;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class unreal4uTestBot extends Base {
    private $latitude = 0.0;

    private $longitude = 0.0;

    /**
     * @param array $postData
     * @return TelegramMethods
     */
    public function createAnswer(array $postData=[]): TelegramMethods
    {
        $this->extractBasicInformation($postData);
        if ($this->userId !== UNREAL4U_ID) {
            return $this->invalidUser();
        }

        switch ($this->botCommand) {
            case 'start':
                $this->createSimpleMessageStub();
                return $this->start();
                break;
            case '/end':
                return new GetMe();
            case 'help':
                $this->createSimpleMessageStub();
                return $this->help();
                break;
            case 'get_time_for_timezone':
            case '':
                $this->createSimpleMessageStub();
                $this->logger->debug('Object data is', [
                    'command' => $this->botCommand,
                    'subArgs' => $this->subArguments,
                    'text' => $this->message->text,
                ]);
                return $this->checkRawInput();
                break;
            default:
                return new GetMe();
                break;
        }
    }

    protected function invalidUser(): SendMessage
    {
        $this->createSimpleMessageStub();
        $this->response->text = 'This bot is intended to be used for internal development only';
        $this->logger->error('Unauthorized access to bot', ['userId' => $this->userId, 'chatId' => $this->chatId]);

        return $this->response;
    }

    /**
     * Action to execute when botCommand is set
     * @return SendMessage
     */
    protected function start(): SendMessage
    {
        $this->logger->debug('[CMD] Inside START');
        $this->response->text = sprintf(
            _('Welcome! Consult /help at any time to get a list of command and options.')
        );

        // Complete with the text from the help page
        $this->help();

        return $this->response;
    }

    /**
     * Action to execute when botCommand is set
     * @return SendMessage
     */
    protected function help(): SendMessage
    {
        $this->logger->debug('[CMD] Inside HELP');
        $messageText  = '*Example commands:*'.PHP_EOL;
        $messageText .= '- `/get_time_for_timezone America/Santiago` -> Displays the current time in America/Santiago'.PHP_EOL;
        $messageText .= '- `America/Santiago` -> Displays the current time in America/Santiago'.PHP_EOL;
        $messageText .= '- `London` -> Will display a selection for which London you actually mean'.PHP_EOL;
        $messageText .= '- `Rotterdam` -> Will display the time for the timezone Europe/Amsterdam'.PHP_EOL;
        //$messageText .= '`/set_display_format en-US` -> Sets the display format, use a valid locale'.PHP_EOL;
        $messageText .= '- You can also send a location (Works from phone only)';

        $this->response->text .= $messageText;
        return $this->response;
    }

    private function checkRawInput(): SendMessage
    {
        if ($this->botCommand === '') {
            $timezone = $this->performGeonamesSearch();
        } else {
            $timezone = '[botCommand given]';
        }

        $this->response->text = sprintf(
            'Your input was: %s, which corresponds to the following timezone: %s',
            $this->message->text,
            $timezone
        );

        return $this->response;
    }

    private function createButton(array $geonamesPlace): Button
    {
        $button = new Button();
        $button->text = $geonamesPlace['toponymName'];
        $button->callback_data = json_encode([
            'name' => $geonamesPlace['toponymName'],
            'lat' => $geonamesPlace['lat'],
            'lon' => $geonamesPlace['lng'],
        ]);

        return $button;
    }

    private function performGeonamesSearch(): string
    {
        $answer = $this->httpClient->get(sprintf(
            'http://api.geonames.org/searchJSON?name=%s&maxRows=%s&username=%s',
            urlencode($this->message->text),
            6,
            GEONAMES_API_USERID
        ));
        $geonamesResponse = json_decode((string)$answer->getBody(), true);
        $this->logger->info('Completed call to Geonames');

        if (count($geonamesResponse) > 1) {
            $i = 0;
            $inlineKeyboardMarkup = new Markup();
            $firstButton = $secondButton = null;

            foreach ($geonamesResponse['geonames'] as $geoNamesPlace) {
                if ($i !== 0 && $i % 2 == 0) {
                    $inlineKeyboardMarkup->inline_keyboard[] = [$firstButton, $secondButton];
                    $firstButton = $secondButton = null;
                }

                if ($i % 2 == 0) {
                    $firstButton = $this->createButton($geoNamesPlace);
                } else {
                    $secondButton = $this->createButton($geoNamesPlace);
                }

                $i++;
            }

            $this->response->reply_markup = $inlineKeyboardMarkup;
        } else {
            $this->latitude = $geonamesResponse[0]['lat'];
            $this->longitude = $geonamesResponse[0]['lng'];
        }

        return '';
    }

    private function formatTimezone(): TheTimeBot
    {
        $return = '';
        $parts = explode('/', $this->arguments);
        foreach ($parts as $part) {
            $return .= ucwords($part) . '/';
        }

        $this->arguments = trim($return, '/');
        return $this;
    }

    private function getTheTime(): string
    {
        $this->logger->debug(sprintf('Calculating the time for timezone "%s"', $this->arguments));

        $localization = new localization();
        $acceptedTimezone = $localization->setTimezone($this->arguments);

        if ($acceptedTimezone === $this->arguments) {
            $theTime = $localization->formatSimpleDate(0, $acceptedTimezone).' '.$localization->formatSimpleTime(0, $acceptedTimezone);
            $theTime .= '; Offset: '.$localization->getTimezoneOffset('hours');
        } else {
            throw new \Exception('Invalid timezone, please try again');
        }

        return $theTime;
    }

    /**
     * Returns an array with valid subcommands for the bot
     * @return array
     */
    public function validSubcommands(): array
    {
        return [
            'start',
            'setup',
            'get_notify_url',
            'regenerate_notify_url',
            'help',
        ];
    }
}

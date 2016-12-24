<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Psr\Http\Message\ResponseInterface;
use unreal4u\localization;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramBots\Exceptions\InvalidCallbackContents;
use unreal4u\TelegramBots\Exceptions\InvalidTimezoneId;

class unreal4uTestBot extends Base {
    private $latitude = 0.0;

    private $longitude = 0.0;

    private $timezoneId = '';

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
            case 'get_time_for_timezone': // The original command
            case 'get_time_for_latitude': // Alias
            case 'get': // Alias
            case '':
                $this->logger->debug('Object data is', [
                    'command' => $this->botCommand,
                    'subArgs' => $this->subArguments,
                    'text' => $this->message->text,
                ]);
                if ('/'.$this->botCommand === trim($this->message->text)) {
                    return $this->informAboutEmptyCommand();
                }

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
        $messageText .= '- `/get America/Santiago` -> Displays the current time in *America/Santiago*'.PHP_EOL;
        $messageText .= '- `America/Santiago` -> Displays the current time in *America/Santiago*'.PHP_EOL;
        $messageText .= '- `Rotterdam` -> Will display a selection for which Rotterdam you actually mean'.PHP_EOL;
        $messageText .= '- `Eindhoven` -> Will display the time for the timezone *Europe/Amsterdam*'.PHP_EOL;
        //$messageText .= '`/set_display_format en-US` -> Sets the display format, use a valid locale'.PHP_EOL;
        $messageText .= '- You can also send a location (Works from phone only)';

        $this->response->text .= $messageText;
        return $this->response;
    }

    protected function informAboutEmptyCommand(): SendMessage
    {
        $this->createSimpleMessageStub();
        $this->logger->warning('Empty botcommand detected');
        $this->response->text = 'Sorry but I don\'t understand this option, please check `/help`';

        return $this->response;
    }

    private function fillFinalResponse(): unreal4uTestBot
    {
        $this->response->text = sprintf(
            'The date & time in *%s* is now *%s hours*',
            $this->timezoneId,
            $this->getTheTime()
        );

        return $this;
    }

    private function checkRawInput(): TelegramMethods
    {
        if ($this->isValidTimeZone($this->message->text) === false) {
            if (!empty($this->subArguments)) {
                try {
                    $this->createEditableMessage();
                    $this->decodeCallbackContents();
                    $this->getTimeForLatitude();
                } catch (\Exception $e) {
                    $this->logger->error('Problem while decoding callback contents', [
                        'errorMsg' => $e->getMessage(),
                        'errorCode' => $e->getCode(),
                        'subArguments' => $this->subArguments,
                        'botCommand' => $this->botCommand,
                        'messageText' => $this->message->text,
                    ]);
                }
            } else {
                $this->sendThinkingCommand();

                // Worst case scenario: we must perform a Geonames search
                $this->createSimpleMessageStub();
                $this->performGeonamesSearch();
            }
        } else {
            // Best case scenario: we have a direct timezoneId
            $this->createSimpleMessageStub();
            $this->fillFinalResponse();
        }

        return $this->response;
    }

    private function createButton(array $geonamesPlace): Button
    {
        $button = new Button();
        $button->text =
            $geonamesPlace['toponymName'].', '.
            $geonamesPlace['adminName1'].', '.
            $geonamesPlace['countryName'];

        $button->callback_data = 'get?lt='.$geonamesPlace['lat'].'&ln='.$geonamesPlace['lng'];

        return $button;
    }

    private function decodeCallbackContents(): unreal4uTestBot
    {
        if (!isset($this->subArguments['lt'], $this->subArguments['ln'])) {
            throw new InvalidCallbackContents('No LAT or LON are set in callback');
        }

        $this->latitude = $this->subArguments['lt'];
        $this->longitude = $this->subArguments['ln'];

        return $this;
    }

    private function performAPIRequest(string $url, string $type): ResponseInterface
    {
        $this->logger->debug('About to perform '.$type.' search', [$url]);
        $beginTime = microtime(true);
        $answer = $this->httpClient->get($url);
        $endTime = microtime(true);
        $this->logger->debug('Finished performing request', ['totalTime' => $endTime - $beginTime]);

        return $answer;
    }

    private function doGeonamesCityLookup(): array
    {
        $url = sprintf(
            'http://api.geonames.org/searchJSON?name_startsWith=%s&maxRows=%d&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&orderby=%s&username=%s',
            urlencode($this->message->text),
            6,
            'ADM3',
            'PPLA1',
            'PPLA2',
            'PPLA3',
            'PPLA4',
            'PPL',
            'PPLC',
            'PCLI',
            //'cities1000',
            'population',
            GEONAMES_API_USERID
        );
        $answer = $this->performAPIRequest($url, 'city');
        return json_decode((string)$answer->getBody(), true);
    }

    private function doGeonamesTimezoneIdLookup(): \stdClass
    {
        $url = sprintf(
            'http://api.geonames.org/timezoneJSON?lat=%s&lng=%s&username=%s',
            $this->latitude,
            $this->longitude,
            GEONAMES_API_USERID
        );
        $answer = $this->performAPIRequest($url, 'timezone');

        return json_decode((string)$answer->getBody());
    }

    private function createGeonamesInfoButton(array $geonamesPlaces): Markup
    {
        $inlineKeyboardMarkup = new Markup();

        foreach ($geonamesPlaces['geonames'] as $geoNamesPlace) {
            $inlineKeyboardMarkup->inline_keyboard[] = [$this->createButton($geoNamesPlace)];
        }

        return $inlineKeyboardMarkup;
    }

    private function performGeonamesSearch(): SendMessage
    {
        $geonamesResponse = $this->doGeonamesCityLookup();
        $this->logger->info('Completed call to GeoNames', [
            'query' => $this->message->text,
            'totalResults' => $geonamesResponse['totalResultsCount']
        ]);

        if ($geonamesResponse['totalResultsCount'] === 0) {
            $this->response->text = sprintf(
                'No populated places called *%s* have been found. Maybe try another search?',
                $this->message->text
            );
        } elseif ($geonamesResponse['totalResultsCount'] > 1) {
            $this->response->text = sprintf(
                'There was more than 1 result for your query, please select the most appropiate one from the list below'
            );

            if ($geonamesResponse['totalResultsCount'] > 6) {
                $this->response->text .= '.'.PHP_EOL.'*NOTE*: There are more than 6 results for your search terms, ';
                $this->response->text .= 'try to search for more specific places, you can try appending the name of ';
                $this->response->text .= 'the region or country. Now showing the 6 more relevant results';
            }
            $this->response->reply_markup = $this->createGeonamesInfoButton($geonamesResponse);
        } else {
            $this->latitude = $geonamesResponse['geonames'][0]['lat'];
            $this->longitude = $geonamesResponse['geonames'][0]['lng'];
            // Once we have the latitude, we can perform another geonames lookup to get the timezoneId
            $this->getTimeForLatitude();
        }

        return $this->response;
    }

    /**
     * Get's the time for the already set coordinates
     *
     * @return SendMessage
     * @throws InvalidTimezoneId
     * @throws \Exception
     */
    private function getTimeForLatitude(): TelegramMethods
    {
        $decodedJson = $this->doGeonamesTimezoneIdLookup();

        if ($this->isValidTimeZone($decodedJson->timezoneId)) {
            $this->logger->info('Completed call to GeoNames', [
                'lat' => $this->latitude,
                'lon' => $this->longitude,
                'timezoneId' => $decodedJson->timezoneId,
            ]);

            $this->fillFinalResponse();
        } else {
            $this->logger->error('Invalid timezoneId returned from Geonames', [
                'lat' => $this->latitude,
                'lon' => $this->longitude,
                'timezoneId' => $decodedJson->timezoneId,
            ]);

            throw new InvalidTimezoneId(sprintf('The given timezone ("%s") is not valid', $decodedJson->timezoneId));
        }

        return $this->response;
    }

    /**
     * Validates a timezoneId
     *
     * @param string $timezoneCandidate
     * @return bool
     */
    private function isValidTimeZone(string $timezoneCandidate): bool
    {
        $return = '';
        // Some timezones have underscores as part of their name... which must be converted to ucwords ¬¬
        $this->timezoneId = str_replace('_', ' ', $timezoneCandidate);
        $parts = explode('/', $this->timezoneId);
        foreach ($parts as $part) {
            // Convert all first letter of each word to uppercase
            $return .= ucwords($part) . '/';
        }

        // Convert all spaces back to underscores... ¬¬
        $this->timezoneId = trim(str_replace(' ', '_', $return), '/');

        $localization = new localization();
        if ($localization->isValidTimeZone($this->timezoneId)) {
            return true;
        } else {
            $this->timezoneId = '';
            return false;
        }
    }

    private function getTheTime(): string
    {
        $this->logger->debug(sprintf('Calculating the time for timezone "%s"', $this->timezoneId));

        $localization = new localization();
        $acceptedTimezone = $localization->setTimezone($this->timezoneId);

        if ($acceptedTimezone === $this->timezoneId) {
            $theTime =
                $localization->formatSimpleDate(0, $acceptedTimezone).
                ' '.
                $localization->formatSimpleTime(0, $acceptedTimezone);
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
            'get',
            'get_time_for_timezone',
            'get_time_for_latitude',
            'help',
        ];
    }
}

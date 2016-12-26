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
use unreal4u\TelegramAPI\Telegram\Types\Location;
use unreal4u\TelegramBots\Exceptions\InvalidCallbackContents;
use unreal4u\TelegramBots\Exceptions\InvalidTimezoneId;

class TheTimeBot extends Base {
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
        $this->checkMessageForLocationInput();

        // Default: a simple message
        $this->createSimpleMessageStub();
        switch ($this->botCommand) {
            case 'start':
                return $this->start();
                break;
            case '/end':
                return new GetMe();
            case 'help':
                return $this->help();
                break;
            case 'get_time_for_latitude':
                return $this->getTimeForLatitude();
                break;
            case 'get_time_for_timezone': // The original command
            case 'get': // Alias
            case '':
                $this->logger->debug('Object data is', [
                    'command' => $this->botCommand,
                    'subArgs' => $this->subArguments,
                    'commandSubArguments' => $this->commandSubArguments,
                    'text' => $this->message->text,
                ]);

                // Check for an empty command
                if ('/'.$this->botCommand === trim($this->message->text)) {
                    return $this->informAboutEmptyCommand();
                }

                // We must parse the unsafe data
                return $this->checkRawInput();
                break;
            default:
                return $this->informAboutEmptyCommand();
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
            _('Welcome! Consult `/help` at any time to get a list of command and options.')
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
        $messageText .= '- `Republic of Mozambique` -> Will display the time for the timezone *Africa/Maputo*'.PHP_EOL;
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

    private function checkMessageForLocationInput(): TheTimeBot
    {
        if ($this->message->location instanceof Location) {
            $this->botCommand = 'get_time_for_latitude';
            $this->latitude = $this->message->location->latitude;
            $this->longitude = $this->message->location->longitude;
        }

        return $this;
    }

    private function fillFinalResponse(): TheTimeBot
    {
        // TODO: Change this to "It is now [currentTime] in [timezone]. Offset: X hour(s)
        $this->response->text = sprintf(
            'The date & time in *%s* is now *%s hour(s)*',
            $this->timezoneId,
            $this->getTheTime()
        );
        $this->logger->warning('[OK] Filling final response', ['timezone' => $this->timezoneId]);

        return $this;
    }

    private function checkRawInput(): TelegramMethods
    {
        $argument = '';
        // Everything can come in with or without a bot command
        if ($this->commandSubArguments !== '') {
            // Command sub arguments will be filled when we chain a command with
            $argument = $this->commandSubArguments;
        } elseif (isset($this->subArguments[0])) {
            $argument = $this->subArguments[0];
        }

        if ($this->isValidTimeZone($argument) === false) {
            if ($this->commandSubArguments === '' || !empty($this->subArguments)) {
                // Only perform the latitude check
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

                    // Send at least an error back to the user
                    // TODO Solve this in a more elegant way
                    $this->informAboutEmptyCommand();
                }
            } else {
                $this->sendThinkingCommand();

                // Worst case scenario: we must perform a Geonames search
                $this->createSimpleMessageStub();
                $this->performGeonamesSearch();
            }
        } else {
            // Best case scenario: we have a direct timezoneId
            $this->fillFinalResponse();
        }

        return $this->response;
    }

    private function createButton(array $geonamesPlace): Button
    {
        $button = new Button();

        $button->text = $geonamesPlace['toponymName'].', ';
        if ($geonamesPlace['fcl'] !== 'A') {
            $button->text .= $geonamesPlace['adminName1'] . ', ';
        }
        $button->text .= $geonamesPlace['countryName'];

        $button->callback_data = 'get?lt='.$geonamesPlace['lat'].'&ln='.$geonamesPlace['lng'];

        return $button;
    }

    /**
     * Decodes a callbackQuery
     *
     * @return TheTimeBot
     * @throws InvalidCallbackContents
     */
    private function decodeCallbackContents(): TheTimeBot
    {
        if (!isset($this->subArguments['lt'], $this->subArguments['ln'])) {
            throw new InvalidCallbackContents('No LAT or LON are set in callback');
        }

        $this->latitude = $this->subArguments['lt'];
        $this->longitude = $this->subArguments['ln'];

        return $this;
    }

    /**
     * Does the actual call to the Geonames API and records how long it took to perform the call
     *
     * @param string $url
     * @param string $type
     * @return ResponseInterface
     */
    private function performAPIRequest(string $url, string $type): ResponseInterface
    {
        $this->logger->debug('About to perform '.$type.' search', [$url]);
        $beginTime = microtime(true);
        $answer = $this->httpClient->get($url);
        $endTime = microtime(true);
        $this->logger->warning('[OK] Finished performing Geonames API request', [
            'type' => $type,
            'totalTime' => $endTime - $beginTime
        ]);

        return $answer;
    }

    /**
     * Performs a general search lookup to the Geonames API
     *
     * @return array
     */
    private function doGeonamesSearchLookup(): array
    {
        $url = sprintf(
            'http://api.geonames.org/searchJSON?name_startsWith=%s&maxRows=%d&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&featureCode=%s&orderby=%s&username=%s',
            urlencode($this->commandSubArguments),
            6,
            'ADM3',
            'PPLA1',
            'PPLA2',
            'PPLA3',
            'PPLA4',
            'PPL',
            'PPLC',
            'PCLI',
            'PCLIX',
            //'cities1000',
            'population',
            GEONAMES_API_USERID
        );
        $answer = $this->performAPIRequest($url, 'search');
        return json_decode((string)$answer->getBody(), true);
    }

    /**
     * Performs the timezone lookup based on coordinates to the Geonames API
     *
     * @return \stdClass
     */
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

    /**
     * Creates an inline keyboard button with the given input
     *
     * @param array $geonamesPlaces
     * @return Markup
     */
    private function createGeonamesInfoButton(array $geonamesPlaces): Markup
    {
        $inlineKeyboardMarkup = new Markup();

        foreach ($geonamesPlaces['geonames'] as $geoNamesPlace) {
            $inlineKeyboardMarkup->inline_keyboard[] = [$this->createButton($geoNamesPlace)];
        }

        return $inlineKeyboardMarkup;
    }

    /**
     * @TODO Will filter out very similar results... such as "pozo almonte" which gives the A and P back
     * Maybe do this based on timezone information? This can however only be done if we import the whole of Geonames
     * data in our own database
     *
     * @param array $geonamesResponse
     * @return array
     */
    private function preRenderResults(array $geonamesResponse): array
    {
        /*if ($geonamesResponse['totalResultsCount'] > 1) {
            foreach ($geonamesResponse['geonames'] as $place) {

            }
        }*/

        return $geonamesResponse;
    }

    /**
     * Handles off a search in the Geonames API
     *
     * @return SendMessage
     * @throws InvalidTimezoneId
     */
    private function performGeonamesSearch(): SendMessage
    {
        $geonamesResponse = $this->doGeonamesSearchLookup();
        $this->logger->info('Completed call to GeoNames', [
            'query' => $this->message->text,
            'APITotalResults' => $geonamesResponse['totalResultsCount']
        ]);

        $geonamesResponse = $this->preRenderResults($geonamesResponse);

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
                $this->response->text .= '.'.PHP_EOL.'*NOTE*: There are a total of ';
                $this->response->text .= $geonamesResponse['totalResultsCount'].' results for your search term(s), ';
                $this->response->text .= 'showing the first 6 most relevant places. This search *will improve* ';
                $this->response->text .= 'in the future. If you don\'t see what you\'re looking for, try a timezoneId ';
                $this->response->text .= 'search, which is faster and more accurate.';
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
     * @return TelegramMethods
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
        // Quick escape: don't do any processing if we know for a fact no timezone is given
        if ($timezoneCandidate === '') {
            return false;
        }

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

    /**
     * Calculates what time it is in the set timezoneId
     *
     * @return string
     * @throws \Exception
     */
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

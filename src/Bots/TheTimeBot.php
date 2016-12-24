<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\localization;

class TheTimeBot extends Base
{
    protected $command = '';

    protected $arguments = '';

    protected $update;

    public function createAnswer(array $postData=[]): TelegramMethods
    {
        $update = new Update($postData, $this->logger);
        $this->logger->debug('Incoming data', $postData);
        return $this->performAction($update);
    }

    public function performAction(Update $update): SendMessage
    {
        if (empty($update->message->text) && !empty($update->edited_message->text)) {
            // We'll treat updates the same way as simple messages, maybe in the future edit the original sent msg as well?
            $update->message = $update->edited_message;
        }

        if (!empty($update->message->entities)) {
            $this->command = trim(strtolower(mb_substr(
                $update->message->text,
                $update->message->entities[0]->offset + 1,
                $update->message->entities[0]->length
            )));
            $this->arguments = trim(substr($update->message->text, $update->message->entities[0]->length));
            $this->logger->info(sprintf(
                'The requested command is "%s". Arguments are "%s". Sent text is: "%s"',
                $this->command,
                $this->arguments,
                $update->message->text
            ));
        }

        if (!empty($update->message->location)) {
            $this->command = 'getTimeByLocation';
            $this->arguments = $update->message->location;
        }

        $this->update = $update;

        return $this->prepareUserMessage($this->constructBasicMessage(), $update->message->chat);
    }

    protected function constructBasicMessage(): string
    {
        $messageText = '';
        switch ($this->command) {
            case 'start':
                $messageText = 'Welcome! Consult /help at any time to get a list of command and options.'.PHP_EOL;
            case 'help':
                $messageText .= '*Example commands:*'.PHP_EOL;
                $messageText .= '- `/get_time_for_timezone America/Santiago` -> Displays the current time in America/Santiago'.PHP_EOL;
                //$messageText .= '`/set_display_format en-US` -> Sets the display format, use a valid locale'.PHP_EOL;
                $messageText .= '- You can also send a location (Works from phone only)';
                break;
            case 'getTimeByLocation':
                $this->logger->debug(sprintf('Asking Geonames what timezone is lat: %s and lng: %s', $this->arguments->latitude, $this->arguments->longitude));
                $answer = $this->httpClient->get(sprintf(
                    'http://api.geonames.org/timezoneJSON?lat=%s&lng=%s&username=%s',
                    $this->arguments->latitude,
                    $this->arguments->longitude,
                    'TheTimeBotTelegram'
                ));
                $decodedJson = json_decode((string)$answer->getBody());
                $this->arguments = $decodedJson->timezoneId;
                $this->logger->info(sprintf('Timezone we must get data from is %s, passing arguments on to get_time_for_timezone', $this->arguments));
            case 'get_time_for_timezone':
                if (empty($this->arguments)) {
                    $this->logger->info('Valid command found but invalid arguments');
                    $messageText = 'Please provide a valid timezone identifier';
                } else {
                    try {
                        $this->formatTimezone();
                        $messageText = sprintf('The date & time in *%s* is now *%s hours*', $this->arguments, $this->getTheTime());
                        $this->logger->warning(sprintf('[OK] "%s" is a valid timezone, sending information back to user', $this->arguments));
                    } catch (\Exception $e) {
                        $this->logger->warning('Invalid timezone detected', ['timezone' => $this->arguments]);
                        $messageText = sprintf(
                            'Sorry but "*%s*" is not a valid timezone identifier. Please check [the following list](%s) for all possible timezone identifiers',
                            $this->arguments,
                            'http://php.net/manual/en/timezones.php'
                        );
                    }
                }
                break;
            case 'set_display_format':
                $messageText = 'Sorry but this command is not yet implemented, check later!';
                break;
            default:
                $this->logger->warning('Invalid command detected', ['command' => $this->command, 'arguments' => $this->arguments, 'text' => $this->update->message->text]);
                $messageText = 'Sorry but I don\'t understand this option, please check /help';
                break;
        }

        return $messageText;
    }

    protected function prepareUserMessage(string $messageText, Chat $chat): SendMessage
    {
        $this->logger->debug('Preparing the actual message to be sent to the user', ['text' => $messageText, 'chatId' => $chat->id]);
        $this->response = new SendMessage();
        $this->response->chat_id = $chat->id;
        $this->response->text = $messageText;
        $this->response->parse_mode = 'Markdown';

        return $this->response;
    }

    protected function formatTimezone(): TheTimeBot
    {
        $return = '';
        $this->arguments = str_replace('_', ' ', $this->arguments);
        $parts = explode('/', $this->arguments);
        foreach ($parts as $part) {
            $return .= ucwords($part) . '/';
        }

        $this->arguments = trim(str_replace(' ', '_', $return), '/');
        return $this;
    }

    protected function getTheTime(): string
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

    public function validSubcommands(): array
    {
        return [
            'start',
            'help',
            'getTimeByLocation',
            'get_time_for_timezone',
            'set_display_format',
        ];
    }
}

<?php

namespace unreal4u\Bots;

use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\localization;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class TheTimeBot implements BotsInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string
     */
    private $token;

    private $HTTPClient = null;

    protected $command = '';

    protected $arguments = '';

    public function __construct(LoggerInterface $logger, string $token)
    {
        $this->logger = $logger;
        $this->token = $token;
        $this->HTTPClient = new Client();
    }

    public function run(array $postData=[])
    {
        $update = new Update($postData, $this->logger);
        $this->logger->debug('Incoming data', $postData);
        $this->performAction($update);
	return $this;
    }

    public function performAction(Update $update) 
    {
        if (empty($update->message->text) && !empty($update->edited_message->text)) {
            // We'll treat updates the same way as simple messages, maybe in the future edit the original sent msg as well?
            $update->message = $update->edited_message;
        }

        if (!empty($update->message->entities)) {
            $this->command = trim(strtolower(mb_substr($update->message->text, $update->message->entities[0]->offset + 1, $update->message->entities[0]->length)));
            $this->arguments = trim(substr($update->message->text, $update->message->entities[0]->length));
            $this->logger->info(sprintf('The requested command is "%s". Arguments are "%s"', $this->command, $this->arguments));
        }

        if (!empty($update->message->location)) {
            $this->command = 'getTimeByLocation';
            $this->arguments = $update->message->location;
        }

        $sendMessage = $this->prepareUserMessage($this->constructBasicMessage(), $update->message->chat);
        $this->sendToUser($sendMessage);

        return $this;
    }

    protected function constructBasicMessage(): string
    {
        switch ($this->command) {
            case 'start':
                $messageText = 'Welcome! Consult /help to get a list of command and options.';
                break;
            case 'help':
                $messageText = 'Example commands:'.PHP_EOL;
                $messageText .= '`/get_time_for_timezone America/Santiago` -> Displays the current time in America/Santiago'.PHP_EOL;
                $messageText .= '`/set_display_format en-US` -> Sets the display format, use a valid locale'.PHP_EOL;
                $messageText .= 'You can also send a custom location (Works only from your phone for now)';
                break;
            case 'getTimeByLocation':
                $messageText = 'Knowing what time it is based on a custom location will soon be implemented! ';
                $messageText .= sprintf('Chosen location: %.05f lon, %.05f lat', $this->arguments->longitude, $this->arguments->latitude);
                $answer = $this->HTTPClient->get(sprintf(
                    'http://api.geonames.org/timezoneJSON?lat=%s&lng=%s&username=%s',
                    $this->arguments->latitude,
                    $this->arguments->longitude,
                    'TheTimeBotTelegram'
                ));
                $decodedJson = json_decode((string)$answer->getBody());
                $this->arguments = $decodedJson->timezoneId;
                $this->logger->info(sprintf('Timezone we must get data from is %s, passing on to next function', $timezoneId));
            case 'get_time_for_timezone':
                if (empty($this->arguments)) {
                    $this->logger->warning('Valid command found but invalid arguments');
                    $messageText = 'Please provide a valid timezone identifier';
                } else {
                    try {
                        $this->formatTimezone();
                        $messageText = sprintf('The date & time in *%s* is now *%s*', $this->arguments, $this->getTheTime());
                        $this->logger->info(sprintf('"%s" is a valid timezone, sending information back to user', $this->arguments));
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
                $this->logger->warning('Invalid command detected', ['command' => $this->command, 'arguments' => $this->arguments]);
                $messageText = 'Sorry but I don\'t understand this option, please check /help';
                break;
        }

        return $messageText;
    }

    protected function sendToUser(SendMessage $sendMessage): TelegramTypes
    {
        $this->logger->debug('Sending the message to user');
        $tgLog = new TgLog($this->token, $this->logger, $this->HTTPClient);
        return $tgLog->performApiRequest($sendMessage);
    }

    protected function prepareUserMessage(string $messageText, Chat $chat): SendMessage
    {
        $this->logger->debug('Preparing the actual message to be sent to the user', ['text' => $messageText, 'chatId' => $chat->id]);
        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $chat->id;
        $sendMessage->text = $messageText;
        $sendMessage->parse_mode = 'Markdown';

        return $sendMessage;
    }

    protected function formatTimezone()
    {
        $return = '';
        $parts = explode('/', $this->arguments);
        foreach ($parts as $part) {
            $return .= ucwords($part) . '/';
        }

        $this->arguments = trim($return, '/');
        return $this;
    }

    protected function getTheTime(): string
    {
        $this->logger->debug(sprintf('Calculating the time for timezone "%s"', $this->arguments));
        $theTime = '';

        $localization = new localization();
        $acceptedTimezone = $localization->setTimezone($this->arguments);

        if ($acceptedTimezone === $this->arguments) {
            $theTime = $localization->formatSimpleDate(0, $acceptedTimezone).' '.$localization->formatSimpleTime(0, $acceptedTimezone);
        } else {
            throw new \Exception('Invalid timezone, please try again');
        }

        return $theTime;
    }
}

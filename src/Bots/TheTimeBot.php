<?php

namespace unreal4u\Bots;

use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\TgLog;
use unreal4u\localization;
use Psr\Log\LoggerInterface;

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

    protected $command = '';

    protected $arguments = '';

    public function __construct(LoggerInterface $logger, string $token)
    {
        $this->logger = $logger;
        $this->token = $token;
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
        if (!empty($update->message->text)) {
            $spacePosition = strpos($update->message->text, ' ');
            if ($spacePosition === false) {
                $spacePosition = strlen($update->message->text);
            } else {
                $this->arguments = substr($update->message->text, $spacePosition + 1);
            }
            $this->command = strtolower(trim(substr($update->message->text, 1, $spacePosition)));
            $this->logger->info(sprintf('The requested command is "%s". Arguments are "%s"', $this->command, $this->arguments));
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
                break;
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
        $tgLog = new TgLog($this->token, $this->logger);
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

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Ramsey\Uuid\Uuid;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

class UptimeMonitorBot extends Base {
    /**
     * @var SendMessage
     */
    protected $response = null;

    public function run(array $postData=[]): TelegramMethods
    {
        $this->extractBasicInformation($postData);

        $this->response = new SendMessage();
        $this->response->chat_id = $this->chatId;
        $this->response->reply_to_message_id = $this->updateObject->message->message_id;

        switch ($this->action) {
            case 'start':
                return $this->start();
                break;
            case 'setup':
                return $this->setup();
                break;
            case 'get_notify_url':
                return $this->getNotifyUrl();
                break;
            case 'regenerate_notify_url':
                return $this->regenerateNotifyUrl();
                break;
            case 'help':
            default:
                return $this->help();
        }
    }

    protected function start(): SendMessage
    {
        $this->response->text = sprintf(
            _('Welcome to the UptimeMonitorBot! This bot will notify you if any of your sites go down!%s'),
            PHP_EOL
        );
        // Complete with the text from the help page
        $this->help();
        return $this->response;
    }

    protected function help(): SendMessage
    {
        $messageText  = _('The available commands are: ').PHP_EOL;
        $messageText .= _('`setup`: Guides you through the setup of a new monitor').PHP_EOL;
        $messageText .= _('`get_notify_url`: Will return the callback url to be filled in in https://uptimerobot.com');

        $this->response->text .= $messageText;
        return $this->response;
    }

    protected function setup(): SendMessage
    {
        $this->response->text = _('');
        return $this->response;
    }

    protected function getNotifyUrl(): SendMessage
    {
        // TODO get UUID from DB
        if (empty($uuid)) {
            $uuid = $this->generateNewUuid4();
        }

        $this->response->text = 'Fill in the following url in the box: `https://telegram.unreal4u.com/UptimeMonitorBot/'.$uuid.'?`';
        return $this->response;
    }

    protected function regenerateNotifyUrl(): SendMessage
    {
        // TODO save new UUID to DB
        return $this->getNotifyUrl();
    }

    private function generateNewUuid4(): string
    {
        return Uuid::uuid4()->toString();
    }
}

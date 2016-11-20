<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Ramsey\Uuid\Uuid;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramBots\Models\Entities\Events;
use unreal4u\TelegramBots\Models\Entities\Monitors;

class UptimeMonitorBot extends Base {
    public function createAnswer(array $postData=[]): TelegramMethods
    {
        $this->extractBasicInformation($postData);
        // Database connections are mandatory for all operations on this bot
        $this->setupDatabaseSettings();

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

    public function createNotificationMessage(Events $event): SendMessage
    {
        $monitor = $this->db
            ->getRepository('Monitors')
            ->find($event->getMonitorId());

        if (!empty($monitor)) {
            $this->response->text = sprintf(
                'According to monitor %s for %s, you site is currently %s',
                '[MONITOR]',
                '[SITE]',
                '[STATUS]'
            );
        }
    }

    protected function start(): SendMessage
    {
        $this->response->text = sprintf(
            _('Welcome to the UptimeMonitorBot! This bot will notify you if any of your sites go down!%s'),
            PHP_EOL
        );

        $monitor = $this->db
            ->getRepository('Monitors')
            ->findOneBy(['userId' => $this->userId, 'chatId' => $this->chatId])
        ;

        if (empty($monitor)) {
            $this->getNotifyUrl();
        } else {
            // Complete with the text from the help page
            $this->help();
        }
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
            $uuid = $this->regenerateNotifyUrl();
        }

        $this->response->text .= 'Fill in the following url in the box: `https://telegram.unreal4u.com/UptimeMonitorBot/'.$uuid.'?`';
        return $this->response;
    }

    /**
     * Will generate a new monitorId in our database
     *
     * @return string
     */
    private function regenerateNotifyUrl(): string
    {
        $monitor = new Monitors();
        $monitor->setNotifyUrl(Uuid::uuid4()->toString());
        $monitor->setChatId($this->chatId);
        $monitor->setUserId($this->userId);
        $this->db->persist($monitor);
        $this->db->flush();
        return $monitor->getNotifyUrl();
    }
}

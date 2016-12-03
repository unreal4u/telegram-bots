<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Ramsey\Uuid\Uuid;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\EditMessageText;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramBots\Bots\UptimeMonitor\EventManager;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Setup\Step1;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Setup\Step2;
use unreal4u\TelegramBots\Bots\UptimeMonitor\Setup\Step3;
use unreal4u\TelegramBots\Exceptions\InvalidRequest;
use unreal4u\TelegramBots\Exceptions\InvalidSetupRequest;
use unreal4u\TelegramBots\Models\Entities\Events;
use unreal4u\TelegramBots\Models\Entities\Monitors;

class UptimeMonitorBot extends Base {
    /**
     * The base url on which this bot will be listening for events
     */
    const botBaseUrl = 'https://telegram.unreal4u.com/UptimeMonitorBot/';

    /**
     * @var Monitors
     */
    private $monitor = null;

    /**
     * @param array $postData
     * @return TelegramMethods
     */
    public function createAnswer(array $postData=[]): TelegramMethods
    {
        $this->extractBasicInformation($postData);
        // Database connections are mandatory for all operations on this bot
        $this->setupDatabaseSettings('UptimeMonitorBot');

        $this->initializeMonitor();

        switch ($this->botCommand) {
            case 'start':
                $this->createSimpleMessageStub();
                return $this->start();
                break;
            case 'setup':
                return $this->setup();
                break;
            case 'get_notify_url':
                $this->createSimpleMessageStub();
                return $this->getNotifyUrl();
                break;
            case 'regenerate_notify_url':
                $this->createSimpleMessageStub();
                return $this->regenerateNotifyUrl();
                break;
            case 'help':
                $this->createSimpleMessageStub();
                return $this->help();
                break;
            case '':
            default:
                return new GetMe();
                break;
        }
    }

    public function handleUptimeMonitorNotification(array $rawData, string $incomingUuid): EventManager
    {
        $this->setupDatabaseSettings('UptimeMonitorBot');
        $this->checkValidity($incomingUuid);
        $this->logger->info('Found valid incoming UUID, processing the request', ['uuid' => $incomingUuid]);

        $eventManager = new EventManager($this->db);
        $eventManager->fillEvent($rawData, $this->monitor);
        $event = $eventManager->saveEvent();
        $this->logger->debug('Saved event, creating notification message');
        $this->createNotificationMessage($event);

        return $eventManager;
    }

    /**
     * Try to associate a monitor to this user- and chatId
     *
     * @return UptimeMonitorBot
     */
    private function initializeMonitor(): uptimeMonitorBot
    {
        $this->monitor = $this->db
            ->getRepository('UptimeMonitorBot:Monitors')
            ->findOneBy(['userId' => $this->userId, 'chatId' => $this->chatId])
        ;

        if (empty($this->monitor)) {
            $this->regenerateNotifyUrl();
        }

        return $this;
    }

    private function createNotificationMessage(Events $event): TelegramMethods
    {
        $this->monitor = $this->db
            ->getRepository('UptimeMonitorBot:Monitors')
            ->find($event->getMonitorId());

        $this->response = new GetMe();
        if (!empty($this->monitor)) {
            $this->logger->info('Found a monitor corresponding to UUID, creating the message');
            $this->response = new SendMessage();
            $this->response->chat_id = $this->monitor->getChatId();
            // No sense in trying to show webpage if it is down
            $this->response->disable_web_page_preview = true;
            // Allow basic decoration of text through markdown engine
            $this->response->parse_mode = 'Markdown';
            if ($event->getAlertType() === 1) {
                $this->response->text = $this->messageSiteIsDown($event);
            } else {
                $this->response->text = $this->messageSiteIsUp($event);
            }
        }

        return $this->response;
    }

    /**
     * Because the message is so large, make it an apart function
     *
     * @param Events $event
     * @return string
     */
    private function messageSiteIsDown(Events $event): string
    {
        $this->logger->info('Generating DOWN message');
        return sprintf(
            'Attention! Site [%s](%s) is currently *down*!%s_Alert details:_ %s%s_Date of incident:_ %s UTC%s',
            $event->getUrMonitorUrl(),
            $event->getUrMonitorUrl(),
            PHP_EOL,
            $event->getUrAlertDetails(),
            PHP_EOL,
            $event->getUrAlertTime()->format('c'),
            PHP_EOL.'You will be notified when the site is detected as up and running again'
        );
    }

    private function messageSiteIsUp(Events $event): string
    {
        $this->logger->info('Generating UP message');
        /** @var Events $previousEvent */
        $previousEvent = $this->db
            ->getRepository('UptimeMonitorBot:Events')
            ->findOneBy(['urMonitorId' => $event->getUrMonitorId(), 'alertType' => 1]);

        if (!empty($previousEvent)) {
            $this->logger->debug('Found a previous messageId to reply to, setting it', [
                $previousEvent->getTelegramMessageId()
            ]);

            $this->response->reply_to_message_id = $previousEvent->getTelegramMessageId();
        }

        return sprintf('The site %s is currently back up again, happy surfing! :)', $event->getUrMonitorUrl());
    }

    /**
     * Execution of this command
     *
     * @return SendMessage
     */
    protected function start(): SendMessage
    {
        $this->logger->debug('[CMD] Inside START');
        $this->response->text = sprintf(
            _('Welcome to the UptimeMonitorBot! This bot will notify you if any of your sites go down!%s'),
            PHP_EOL
        );

        if (empty($this->monitor)) {
            $this->getNotifyUrl();
        } else {
            // Complete with the text from the help page
            $this->help();
        }
        return $this->response;
    }

    protected function help(): SendMessage
    {
        $this->logger->debug('[CMD] Inside HELP');
        $messageText  = _(sprintf('Your notifyUrl is: %s', self::botBaseUrl.$this->monitor->getNotifyUrl())).PHP_EOL;
        $messageText .= _('The available commands are: ').PHP_EOL;
        $messageText .= _('`setup`: Guides you through the setup of a new monitor').PHP_EOL;
        $messageText .= _('`get_notify_url`: Will return the callback url to be filled in in https://uptimerobot.com');

        $this->response->text .= $messageText;
        return $this->response;
    }

    protected function setup(): TelegramMethods
    {
        $this->logger->debug('[CMD] Inside SETUP');

        switch ($this->subArguments) {
            // No subarguments? We are in the first step, so return a simple message to begin with the rest
            case '':
                $this->createSimpleMessageStub();
                $step = new Step1($this->logger, $this->response);
                $step->generateAnswer();
                break;
            // First step but with subarguments: we must edit the original one
            case 'step=1':
                $this->createEditableMessage();
                $step = new Step1($this->logger, $this->response);
                $step->generateAnswer();
                break;
            case 'step=2':
                $this->createEditableMessage();
                $step = new Step2($this->logger, $this->response);
                $step->setNotifyUrl($this->constructNotifyUrl());
                $step->generateAnswer();
                break;
            case 'step=3':
                $this->createEditableMessage();
                $step = new Step3($this->logger, $this->response);
                $step->generateAnswer();
                break;
            default:
                $this->logger->error('Invalid step detected!', [
                    'botCommand' => $this->botCommand,
                    'subArguments' => $this->subArguments
                ]);
                throw new InvalidSetupRequest('An invalid step has been detected, please check!');
                break;
        }

        return $this->response;
    }

    protected function getNotifyUrl(): SendMessage
    {
        $this->logger->debug('[CMD] Inside GETNOTIFYURL');
        if (empty($this->monitor)) {
            $this->regenerateNotifyUrl();
        }

        $this->response->text .= sprintf(
            'Fill in the following url in the box: `%s?`',
            $this->constructNotifyUrl()
        );
        return $this->response;
    }

    private function constructNotifyUrl(): string
    {
        return sprintf('https://telegram.unreal4u.com/UptimeMonitorBot/%s?', $this->monitor->getNotifyUrl());
    }

    /**
     * Will generate a new monitorId in our database
     *
     * @return Monitors
     */
    private function regenerateNotifyUrl(): Monitors
    {
        $this->monitor = new Monitors();
        $this->monitor->setNotifyUrl(Uuid::uuid4()->toString());
        $this->monitor->setChatId($this->chatId);
        $this->monitor->setUserId($this->userId);
        $this->db->persist($this->monitor);
        $this->db->flush();
        return $this->monitor;
    }

    /**
     * Checks with the UUID in hand whether we have a valid request
     *
     * @param string $uuid
     * @return UptimeMonitorBot
     * @throws InvalidRequest
     */
    private function checkValidity(string $uuid): UptimeMonitorBot
    {
        $this->monitor = $this->db
            ->getRepository('UptimeMonitorBot:Monitors')
            ->findOneBy(['notifyUrl' => $uuid]);

        if (empty($this->monitor)) {
            throw new InvalidRequest('Invalid incoming UUID detected: '.$uuid);
        }

        return $this;
    }

    private function createEditableMessage(): UptimeMonitorBot
    {
        $this->response = new EditMessageText();
        $this->response->message_id = $this->message->message_id;
        $this->response->chat_id = $this->chatId;

        return $this;
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

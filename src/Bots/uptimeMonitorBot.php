<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots;

use Ramsey\Uuid\Uuid;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Methods\GetMe;
use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramBots\Bots\UptimeMonitor\EventManager;
use unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl\Cancel;
use unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl\Confirmation;
use unreal4u\TelegramBots\Bots\UptimeMonitor\RegenerateNotifyUrl\Regenerate;
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
            case '/end':
                $this->dropMonitor();
                return new GetMe();
            case 'setup':
                return $this->setup();
                break;
            case 'get_notify_url':
                $this->createSimpleMessageStub();
                return $this->getNotifyUrl();
                break;
            case 'regenerate_notify_url':
                return $this->regenerateNotifyUrl();
                break;
            case 'help':
                $this->createSimpleMessageStub();
                return $this->help();
                break;
            // Not yet implemented, will be in the nearby future
            case 'lock':
            case 'unlock':
            case '':
            default:
                return new GetMe();
                break;
        }
    }

    /**
     * Handles off a webhook coming from uptimerobot.com
     *
     * @param array $rawData
     * @param string $incomingUuid
     * @return EventManager
     * @throws InvalidRequest
     */
    public function handleUptimeMonitorNotification(array $rawData, string $incomingUuid): EventManager
    {
        $this->setupDatabaseSettings('UptimeMonitorBot');
        $this->checkValidity($incomingUuid);
        $this->logger->info('Found valid incoming UUID, processing the request', ['uuid' => $incomingUuid]);

        $eventManager = new EventManager($this->db);
        if ($this->isValidGetData($rawData)) {
            $eventManager->fillEvent($rawData, $this->monitor);
            $event = $eventManager->saveEvent();
            $this->logger->debug('Saved event, creating notification message');
            $this->createNotificationMessage($event);
        } else {
            $this->logger->warning('Invalid GET data found', [
                'monitorId' => $this->monitor->getId(),
                'chatId' => $this->chatId,
            ]);
            $this->response = new GetMe();
        }

        return $eventManager;
    }

    /**
     * Checks whether the request from uptimerobot.com is valid or not
     *
     * TODO In the future, each monitor engine will have it's own class
     *
     * @param array $rawData
     * @return bool
     */
    private function isValidGetData(array $rawData): bool
    {
        if (!isset(
            $rawData['alertType'],
            $rawData['alertDetails'],
            $rawData['monitorFriendlyName'],
            $rawData['monitorID'],
            $rawData['monitorURL'],
            $rawData['alertDateTime']
        )) {
            return false;
        }

        return true;
    }

    /**
     * Try to associate a monitor to this user- and chatId
     *
     * @return UptimeMonitorBot
     */
    private function initializeMonitor(): uptimeMonitorBot
    {
        $this->monitor = $this->db
            ->getRepository(Monitors::class)
            ->findOneBy(['chatId' => $this->chatId])
        ;

        if (empty($this->monitor)) {
            $this->regenerateNotifyUrlDatabaseEntry();
        }

        return $this;
    }

    /**
     * Logic behind the creation of a new Event, either up or down
     *
     * @param Events $event
     * @return TelegramMethods
     */
    private function createNotificationMessage(Events $event): TelegramMethods
    {
        $this->monitor = $this->db
            ->getRepository(Monitors::class)
            ->find($event->getMonitorId())
        ;

        $this->response = new GetMe();
        if (!empty($this->monitor)) {
            $this->logger->info('Found a monitor corresponding to UUID, creating the message');
            $this->response = new SendMessage();
            $this->response->chat_id = $this->monitor->getChatId();
            // No sense in trying to show webpage if it is down
            $this->response->disable_web_page_preview = true;
            // Allow basic decoration of text through markdown engine
            $this->response->parse_mode = 'HTML';
            if ($event->getAlertType() === 1) {
                $this->response->text = $this->messageSiteIsDown($event);
            } else {
                $this->response->text = $this->messageSiteIsUp($event);
            }
        }

        return $this->response;
    }

    /**
     * When a site goes down, notify the corresponding user
     *
     * @param Events $event
     * @return string
     */
    private function messageSiteIsDown(Events $event): string
    {
        $this->logger->info('Generating DOWN message');
        return sprintf(
            '%s Attention! Site <a href="%s">%s</a> is currently <b>down</b>!%sAlert details: <b>%s</b>%sDate of incident: <b>%s UTC</b>%s',
            "\xF0\x9F\x94\xB4",
            htmlentities($event->getUrMonitorUrl()),
            htmlentities($event->getUrMonitorUrl()),
            PHP_EOL,
            $event->getUrAlertDetails(),
            PHP_EOL,
            $event->getUrAlertTime()->format('Y-m-d H:i:s'),
            PHP_EOL.'You\'ll be notified when the site is up and running again'
        );
    }

    /**
     * When the site is back up, notify the corresponding user
     *
     * @param Events $event
     * @return string
     */
    private function messageSiteIsUp(Events $event): string
    {
        $this->logger->info('Generating UP message');
        /** @var Events $previousEvent */
        $previousEvent = $this->db
            ->getRepository(Events::class)
            ->findOneBy([
                'monitorId' => $this->monitor->getId(),
                'urMonitorId' => $event->getUrMonitorId(),
                'alertType' => 1,
            ]);

        $downDuration = '';
        if ($previousEvent !== null) {
            $this->logger->debug('Found a previous messageId to reply to, setting it', [
                $previousEvent->getTelegramMessageId()
            ]);
            $this->response->reply_to_message_id = $previousEvent->getTelegramMessageId();

            // Calculate human friendly time display
            $interval = $previousEvent->getEventTime()->diff(new \DateTime());
            $downDuration = 'Site was down for ';
            $days = (int)$interval->format('%a');
            $hours = (int)$interval->format('%H');
            if ($days > 0) {
                $downDuration .= $days.' days, ';
            }
            if ($hours > 0) {
                $downDuration .= $hours.' hours, ';
            }
            $downDuration .= $interval->format('%I minutes and %S seconds');
        }

        return sprintf(
            '%s Site <a href="%s">%s</a> is back up, happy surfing! %s',
            "\xF0\x9F\x94\xB5",
            htmlentities($event->getUrMonitorUrl()),
            htmlentities($event->getUrMonitorUrl()),
            $downDuration
        );
    }

    /**
     * Action to execute when botCommand is set
     * @return SendMessage
     */
    protected function start(): SendMessage
    {
        $this->logger->debug('[CMD] Inside START');
        $this->response->text = sprintf(
            _('Welcome to the UptimeMonitorBot! This bot will notify you if any of your sites go down (or up)%s%s%s'),
            PHP_EOL,
            'This bot integrates the https://uptimerobot.com services with Telegram, and is unofficial.',
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

    /**
     * Action to execute when botCommand is set
     * @return SendMessage
     */
    protected function help(): SendMessage
    {
        $this->logger->debug('[CMD] Inside HELP');
        $messageText  = _(sprintf(
            'Your notifyUrl is: `%s`', $this->constructNotifyUrl()
        )).PHP_EOL;
        $messageText .= _('The available commands are: ').PHP_EOL;
        $messageText .= _(
            '`/setup`: Step-by-step guide to help you setup a monitor at https://uptimerobot.com/'
        ).PHP_EOL;
        $messageText .= _(sprintf(
            '`/get_notify_url`: Will return the callback url to be filled in %s',
            'https://uptimerobot.com'
        )).PHP_EOL;
        $messageText .= _(sprintf('%s%s',
            '`/regenerate_notify_url`: Will regenerate the Notification URL, *but will invalidate the previous URL!* ',
            'Use with caution!'
        )).PHP_EOL;
        $messageText .= _(sprintf(
            'For more help, please visit the [wiki page of this bot](%s).',
            'https://github.com/unreal4u/telegram-bots/wiki/Usage:-UptimeMonitorBot'
        )).PHP_EOL;

        $this->response->text .= $messageText;
        return $this->response;
    }

    /**
     * Action to execute when botCommand is set
     * @return TelegramMethods
     * @throws InvalidSetupRequest
     */
    protected function setup(): TelegramMethods
    {
        $this->logger->debug('[CMD] Inside SETUP');

        if (isset($this->subArguments['newMsg']) || !isset($this->subArguments['step'])) {
            $this->createSimpleMessageStub();
        } else {
            $this->createEditableMessage();
        }

        // Hack: define a default action
        if (!isset($this->subArguments['step'])) {
            $this->subArguments['step'] = '1';
        }
        // Do not include a reply_to_message_id in this series, it's only annoying
        $this->response->reply_to_message_id = null;

        switch ($this->subArguments['step']) {
            case '1':
                $step = new Step1($this->logger, $this->response);
                $step->generateAnswer();
                break;
            case '2':
                $step = new Step2($this->logger, $this->response);
                $step->setNotifyUrl($this->constructNotifyUrl());
                $step->generateAnswer();
                break;
            case '2-picture':
                $this->response = new SendPhoto();
                $this->response->chat_id = $this->chatId;
                $step = new Step2($this->logger, $this->response);
                $step->setNotifyUrl($this->constructNotifyUrl());
                $this->response = $step->generatePhotoAnswer();
                break;
            case '3':
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

    /**
     * @return SendMessage
     */
    protected function getNotifyUrl(): SendMessage
    {
        $this->logger->debug('[CMD] Inside GETNOTIFYURL');
        if (empty($this->monitor)) {
            $this->regenerateNotifyUrlDatabaseEntry();
        }

        $this->response->text .= sprintf(
            'Your notification URL is: `%s` (include the question mark).%s',
            $this->constructNotifyUrl(),
            PHP_EOL.PHP_EOL.'*Tip*: Use the `/setup` command for a step-by-step introduction'
        );
        return $this->response;
    }

    protected function regenerateNotifyUrl(): TelegramMethods
    {
        $this->logger->debug('[CMD] Inside REGENERATE_NOTIFY_URL');

        if (isset($this->subArguments['newMsg']) || !isset($this->subArguments['step'])) {
            $this->createSimpleMessageStub();
        } else {
            $this->createEditableMessage();
        }

        // Hack: define a default action
        if (!isset($this->subArguments['step'])) {
            $this->subArguments['step'] = '1';
        }
        // Do not include a reply_to_message_id in this series, it's only annoying
        $this->response->reply_to_message_id = null;

        switch ($this->subArguments['step']) {
            case '1':
                $this->logger->debug('Inside step "1"', ['chatId' => $this->monitor->getChatId()]);
                $step = new Confirmation($this->logger, $this->response);
                $step->generateAnswer();
                break;
            case 'regenerate':
                // Regenerate the notify url for the current chatId
                $this->logger->debug('Inside step "regenerate"', ['chatId' => $this->monitor->getChatId()]);
                $this->regenerateNotifyUrlDatabaseEntry();
                $step = new Regenerate($this->logger, $this->response);
                $step->setNotifyUrl($this->constructNotifyUrl());
                $step->generateAnswer();
                break;
            case 'cancel':
                $this->logger->debug('Inside step "cancel"', ['chatId' => $this->monitor->getChatId()]);
                $step = new Cancel($this->logger, $this->response);
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

    /**
     * Returns the complete URL notify URL for uptimerobot.com (including HTTP_HOST)
     *
     * @return string
     */
    private function constructNotifyUrl(): string
    {
        return sprintf('%s%s?', self::botBaseUrl, $this->monitor->getNotifyUrl());
    }

    /**
     * Will generate a new monitorId in our database
     *
     * @return Monitors
     */
    private function regenerateNotifyUrlDatabaseEntry(): Monitors
    {
        // Create new entry if none exists yet
        if (empty($this->monitor)) {
            $this->logger->debug('Creating new monitor from scratch');
            $this->monitor = new Monitors();
        }

        $this->monitor->setNotifyUrl(Uuid::uuid4()->toString());
        $this->monitor->setChatId($this->chatId);
        // Transfer ownership as well
        $this->monitor->setUserId($this->userId);
        $this->db->persist($this->monitor);
        $this->db->flush();
        $this->logger->info('Set monitor object, now saving to db', [
            'monitorId' => $this->monitor->getId(),
            'chatId' => $this->monitor->getChatId(),
        ]);
        return $this->monitor;
    }

    /**
     * Will remove all monitors related to the current chatId
     * @return UptimeMonitorBot
     */
    private function dropMonitor(): UptimeMonitorBot
    {
        $this->logger->info('Removing entry from database', [
            'monitorId' => $this->monitor->getId(),
            'chatId' => $this->monitor->getChatId(),
        ]);
        $this->db->remove($this->monitor);
        $this->db->flush();

        return $this;
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
            ->getRepository(Monitors::class)
            ->findOneBy(['notifyUrl' => $uuid]);

        if (empty($this->monitor)) {
            throw new InvalidRequest('Invalid incoming UUID detected: '.$uuid);
        }
        $this->logger->info('Valid monitor found', [
            'monitorId' => $this->monitor->getId(),
            'chatId' => $this->monitor->getChatId(),
        ]);

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

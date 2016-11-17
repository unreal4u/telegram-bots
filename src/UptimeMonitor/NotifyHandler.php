<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\UptimeMonitor;

use unreal4u\TelegramBots\Bots\UptimeMonitorBot;
use unreal4u\TelegramBots\Models\Entities\Events;

class NotifyHandler {
    /**
     * @var array
     */
    private $rawData = [];

    public function __construct(array $rawData, string $eventId)
    {
        $this->rawData = $rawData;
    }

    public function saveEvent(): Events
    {
        $event = $this->fillEvent();

        $db->persist($event);
        $db->flush();

        return $event;
    }

    public function sendNotification($event): bool
    {
        #$event = $db->blabla();

        #$uptimeMonitorBot = new UptimeMonitorBot($logger, $token);
        #$uptimeMonitorBot->sendResponseBack($uptimeMonitorBot->createNotificationMessage($event, $db));

        return true;
    }

    private function fillEvent(): Events
    {
        $event = new Events();
        $event->setAlertType($this->rawData['alertType']);
        $event->setIsNotified(false);
        $event->setUrAlertDetails($this->rawData['alertDetails']);
        $event->setUrMonitorFriendlyUrl($this->rawData['monitorFriendlyName']);
        $event->setUrMonitorId($this->rawData['monitorID']);
        $event->setUrMonitorUrl($this->rawData['monitorURL']);

        return $event;
    }
}

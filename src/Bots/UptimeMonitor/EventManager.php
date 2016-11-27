<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Bots\UptimeMonitor;

use Doctrine\ORM\EntityManager;
use unreal4u\TelegramBots\Models\Entities\Events;
use unreal4u\TelegramBots\Models\Entities\Monitors;

class EventManager {
    /**
     * The current event we are working on
     * @var Events
     */
    private $event = null;

    public function __construct(EntityManager $db)
    {
        $this->db = $db;
    }

    /**
     * Receives an event and saves it onto the database
     *
     * @return Events
     */
    public function saveEvent(): Events
    {
        $this->db->persist($this->event);
        $this->db->flush();

        $this->cleanupOldEvents();

        return $this->event;
    }

    public function cleanupOldEvents(): EventManager
    {
        // TODO Delete old records
        return $this;
    }

    public function fillEvent(array $rawData, Monitors $monitor): Events
    {
        $this->event = new Events();
        $this->event->setEventTime();
        $this->event->setAlertType((int)$rawData['alertType']);
        $this->event->setUrAlertDetails($rawData['alertDetails']);
        $this->event->setUrMonitorFriendlyUrl($rawData['monitorFriendlyName']);
        $this->event->setUrMonitorId((int)$rawData['monitorID']);
        $this->event->setUrMonitorUrl($rawData['monitorURL']);
        $this->event->setUrAlertTime((int)$rawData['alertDateTime']);
        $this->event->setIsNotified(false);
        $this->event->setRawData(json_encode($rawData));
        $this->event->setMonitorId($monitor);

        return $this->event;
    }

    public function setEventNotified(int $messageId): Events
    {
        $this->event->setIsNotified(true);
        $this->event->setTelegramMessageId($messageId);

        $this->saveEvent();

        return $this->event;
    }
}

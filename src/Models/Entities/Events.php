<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models\Entities;

use unreal4u\TelegramBots\Models\Base;

/**
 * @Entity
 * @Table(name="events")
 */
class Events extends Base
{
    const ALERT_TYPE_DOWN = 1;

    const ALERT_TYPE_UP = 2;

    /**
     * @var int
     * @Id
     * @Column(type="uuid_binary")
     * @GeneratedValue(strategy="CUSTOM")
     * @CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected $id;

    /**
     * @var \DateTimeImmutable
     * @Column(type="datetime")
     */
    protected $eventTime;

    /**
     * @var int
     * @Column(type="smallint", options={"unsigned"=true})
     */
    protected $alertType = self::ALERT_TYPE_DOWN;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $monitorId;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $isNotified = false;

    /**
     * @param int $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @param \DateTimeImmutable $eventTime
     */
    public function setEventTime(\DateTimeImmutable $eventTime)
    {
        $this->eventTime = $eventTime;
    }

    /**
     * @param int $alertType
     */
    public function setAlertType($alertType)
    {
        $this->alertType = $alertType;
    }

    /**
     * @param string $monitorId
     */
    public function setMonitorId(string $monitorId)
    {
        $this->monitorId = $monitorId;
    }

    /**
     * @param boolean $isNotified
     * @return bool
     */
    public function setIsNotified($isNotified): bool
    {
        $this->isNotified = $isNotified;
        return $this->isIsNotified();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEventTime(): \DateTimeImmutable
    {
        return $this->eventTime;
    }

    /**
     * @return int
     */
    public function getAlertType(): int
    {
        return $this->alertType;
    }

    /**
     * @return string
     */
    public function getMonitorId()
    {
        return $this->monitorId;
    }

    /**
     * @return boolean
     */
    public function isIsNotified(): bool
    {
        return $this->isNotified;
    }
}

<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use unreal4u\TelegramBots\Models\Base;

/**
 * @Entity
 * @Table(name="Events")
 */
class Events extends Base
{
    const ALERT_TYPE_DOWN = 1;

    const ALERT_TYPE_UP = 2;

    /**
     * @var string
     * @Id
     * @Column(type="uuid_binary")
     * @GeneratedValue(strategy="CUSTOM")
     * @CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected $id;

    /**
     * @var string
     * @ManyToOne(targetEntity="monitors")
     * @JoinColumn(referencedColumnName="id")
     */
    protected $monitorId;

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
     * @var int
     * @Column(type="integer", nullable=true)
     */
    protected $urMonitorId;

    /**
     * @var string
     * @Column(type="string", nullable=true)
     */
    protected $urMonitorUrl;

    /**
     * @var string
     * @Column(type="string", nullable=true)
     */
    protected $urMonitorFriendlyUrl;

    /**
     * @var string
     * @Column(type="string", nullable=true)
     */
    protected $urAlertDetails;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $isNotified = false;

    public function __construct()
    {
        $this->monitorId = new ArrayCollection();
    }

    /**
     * @param string $id
     * @return Events
     */
    public function setId(string $id): Events
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param \DateTimeImmutable $eventTime
     * @return Events
     */
    public function setEventTime(\DateTimeImmutable $eventTime): Events
    {
        $this->eventTime = $eventTime;
        return $this;
    }

    /**
     * @param int $alertType
     * @return Events
     */
    public function setAlertType($alertType): Events
    {
        $this->alertType = $alertType;
        return $this;
    }

    /**
     * @param int $urMonitorId
     * @return Events
     */
    public function setUrMonitorId($urMonitorId): Events
    {
        $this->urMonitorId = $urMonitorId;
        return $this;
    }

    /**
     * @param string $urMonitorUrl
     * @return Events
     */
    public function setUrMonitorUrl($urMonitorUrl): Events
    {
        $this->urMonitorUrl = $urMonitorUrl;
        return $this;
    }

    /**
     * @param string $urMonitorFriendlyUrl
     * @return Events
     */
    public function setUrMonitorFriendlyUrl($urMonitorFriendlyUrl): Events
    {
        $this->urMonitorFriendlyUrl = $urMonitorFriendlyUrl;
        return $this;
    }

    /**
     * @param string $urAlertDetails
     * @return Events
     */
    public function setUrAlertDetails($urAlertDetails): Events
    {
        $this->urAlertDetails = $urAlertDetails;
        return $this;
    }

    /**
     * @param string $monitorId
     * @return Events
     */
    public function setMonitorId(string $monitorId): Events
    {
        $this->monitorId = $monitorId;
        return $this;
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
    public function getMonitorId(): string
    {
        return $this->monitorId;
    }

    /**
     * @return int
     */
    public function getUrMonitorId(): int
    {
        return $this->urMonitorId;
    }

    /**
     * @return string
     */
    public function getUrMonitorUrl(): string
    {
        return $this->urMonitorUrl;
    }

    /**
     * @return string
     */
    public function getUrMonitorFriendlyUrl(): string
    {
        return $this->urMonitorFriendlyUrl;
    }

    /**
     * @return string
     */
    public function getUrAlertDetails(): string
    {
        return $this->urAlertDetails;
    }

    /**
     * @return boolean
     */
    public function isIsNotified(): bool
    {
        return $this->isNotified;
    }
}

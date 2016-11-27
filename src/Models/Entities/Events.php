<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models\Entities;

use unreal4u\TelegramBots\Models\Base;

/**
 * @Entity
 * @Table(name="Events",
 *     indexes={
 *         @Index(name="K_urMonitorId", columns={"urMonitorId"})
 *     })
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
     * @var Monitors
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
     * @var \DateTimeImmutable
     * @Column(type="datetime", nullable=true)
     */
    protected $urAlertDateTime = null;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    protected $isNotified = false;

    /**
     * The message id of the previous message (to be able to reply to it)
     *
     * @var int
     * @Column(type="integer")
     */
    protected $telegramMessageId = 0;

    /**
     * Raw data as we received it from the uptime monitor. Useful for debugging
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    protected $rawData = '';

    public function __construct()
    {
        #$this->monitorId = new ArrayCollection();
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
     * @return Events
     */
    public function setEventTime(): Events
    {
        $this->eventTime = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $this;
    }

    /**
     * @param int $alertType
     * @return Events
     */
    public function setAlertType(int $alertType): Events
    {
        $this->alertType = $alertType;
        return $this;
    }

    /**
     * @param int $urMonitorId
     * @return Events
     */
    public function setUrMonitorId(int $urMonitorId): Events
    {
        $this->urMonitorId = $urMonitorId;
        return $this;
    }

    /**
     * @param string $urMonitorUrl
     * @return Events
     */
    public function setUrMonitorUrl(string $urMonitorUrl): Events
    {
        $this->urMonitorUrl = $urMonitorUrl;
        return $this;
    }

    /**
     * @param string $urMonitorFriendlyUrl
     * @return Events
     */
    public function setUrMonitorFriendlyUrl(string $urMonitorFriendlyUrl): Events
    {
        $this->urMonitorFriendlyUrl = $urMonitorFriendlyUrl;
        return $this;
    }

    /**
     * @param string $urAlertDetails
     * @return Events
     */
    public function setUrAlertDetails(string $urAlertDetails): Events
    {
        $this->urAlertDetails = $urAlertDetails;
        return $this;
    }

    /**
     * Receives a timestamp and saves it as a DateTimeImmutable object
     *
     * @param int $urAlertDateTime
     * @return Events
     */
    public function setUrAlertTime(int $urAlertDateTime): Events
    {
        $urAlertTime = new \DateTime();
        $urAlertTime->setTimestamp($urAlertDateTime);
        $urAlertTime->setTimezone(new \DateTimeZone('UTC'));

        $this->urAlertDateTime = \DateTimeImmutable::createFromMutable($urAlertTime);
        return $this;
    }

    /**
     * @param Monitors $monitorId
     * @return Events
     */
    public function setMonitorId(Monitors $monitor): Events
    {
        $this->monitorId = $monitor;
        return $this;
    }

    /**
     * @param bool $isNotified
     * @return bool
     */
    public function setIsNotified(bool $isNotified): bool
    {
        $this->isNotified = $isNotified;
        return $this->isIsNotified();
    }

    /**
     * @param string $rawData
     * @return Events
     */
    public function setRawData(string $rawData = ''): Events
    {
        $this->rawData = $rawData;
        return $this;
    }

    /**
     * @param int $telegramMessageId
     * @return Events
     */
    public function setTelegramMessageId(int $telegramMessageId): Events
    {
        $this->telegramMessageId = $telegramMessageId;
        return $this;
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
        return $this->monitorId->getId();
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
     * @return \DateTimeImmutable
     */
    public function getUrAlertTime(): \DateTimeImmutable
    {
        return $this->urAlertDateTime;
    }

    /**
     * @return boolean
     */
    public function isIsNotified(): bool
    {
        return $this->isNotified;
    }

    /**
     * @return string
     */
    public function getRawData(): string
    {
        return $this->rawData;
    }

    /**
     * @return int
     */
    public function getTelegramMessageId(): int
    {
        return $this->telegramMessageId;
    }
}

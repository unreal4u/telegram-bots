<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models\Entities;

use Ramsey\Uuid\Uuid;

/**
 * @Entity
 * @Table(name="monitors")
 */
class Monitors
{
    const TYPE_WEBHOOK = 0;

    const TYPE_SELFCHECK = 1;

     /**
      * @var int
      * @Id
      * @Column(type="uuid_binary")
      * @GeneratedValue(strategy="CUSTOM")
      * @CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
      */
    protected $id;

    /**
     * @var int
     * @Column(type="integer")
     */
    protected $chatId;

    /**
     * @var string
     * @Column(type="uuid", nullable=true)
     */
    protected $notifyUrl;

    /**
     * @var int
     * @Column(type="smallint", options={"unsigned"=true})
     */
    protected $type = self::TYPE_WEBHOOK;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $monitorAPIKey;

    /**
     * @return int
     * @Column(type="integer")
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getChatId()
    {
        return $this->chatId;
    }

    /**
     * @param int $chatId
     */
    public function setChatId($chatId)
    {
        $this->chatId = $chatId;
    }

    /**
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl)
    {
        if (empty($notifyUrl)) {
            $notifyUrl = Uuid::uuid4()->toString();
        }
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getMonitorAPIKey()
    {
        return $this->monitorAPIKey;
    }

    /**
     * @param string $monitorAPIKey
     */
    public function setMonitorAPIKey($monitorAPIKey)
    {
        $this->monitorAPIKey = $monitorAPIKey;
    }
}

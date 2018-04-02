<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Exceptions;

class ChatIsBlacklisted extends \LogicException {
    private $blacklistedChatId = 0;

    public function setBlacklistedChatId(int $blacklistedChatId): self
    {
        $this->blacklistedChatId = $blacklistedChatId;
        return $this;
    }

    public function getBlacklistedChatId(): int
    {
        return $this->blacklistedChatId;
    }
}

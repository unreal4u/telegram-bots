<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\tests\Mock;

use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramBots\Bots\Base;

/**
 * Mocks the base class to test specific functionality
 */
class BaseMock extends Base
{
    public function createAnswer(array $postData = []): TelegramMethods
    {
        // TODO: Implement run() method.
    }

    public function testExtractBasicInformation(array $postData)
    {
        return parent::extractBasicInformation($postData);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getAction(): string
    {
        return $this->botCommand;
    }

    public function getUpdateObject(): Update
    {
        return $this->updateObject;
    }
}

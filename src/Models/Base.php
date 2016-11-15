<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models;

use Ramsey\Uuid\Uuid;

abstract class Base
{
    /**
     * Returns a random uuid v4 string, to be used before inserting into db
     *
     * @return string
     */
    public function generateRandomUuid()
    {
        return Uuid::uuid4()->toString();
    }
}

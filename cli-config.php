<?php

declare(strict_types = 1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use unreal4u\TelegramBots\DatabaseWrapper;

$wrapper = new DatabaseWrapper();
return ConsoleRunner::createHelperSet($wrapper->getEntity('UptimeMonitorBot'));

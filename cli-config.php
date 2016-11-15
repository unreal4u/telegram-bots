<?php

declare(strict_types = 1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;

include('Doctrine/bootstrap.php');

return ConsoleRunner::createHelperSet($toolbox->getToolbox('mysqlStorage'));

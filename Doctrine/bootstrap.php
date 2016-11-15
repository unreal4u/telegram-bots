<?php

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use unreal4u\TelegramBots\Models\Configuration;
use unreal4u\TelegramBots\Models\Toolbox;

chdir(dirname(__FILE__).'/../');
include('vendor/autoload.php');

$yamlConfFiles[] = Yaml::parse(file_get_contents('config/config.vagrant.yml'));
$mainConfiguration = new Configuration();
$processor = new Processor();
$finalConfiguration = $processor->processConfiguration($mainConfiguration, $yamlConfFiles);

$myToolbox = $finalConfiguration['mysql'];
$toolbox = new Toolbox(false);
$toolbox->setToolbox($myToolbox['name'], [
    'driver' => $myToolbox['driver'],
    'user' => $myToolbox['dbuser'],
    'password' => $myToolbox['dbpass'],
    'dbname' => $myToolbox['dbname'],
    'charset' => $myToolbox['charset'],
    'default_table_options' => $myToolbox['default_table_options'],
], $myToolbox['extra_types']);

return $toolbox->getToolbox('mysqlStorage');

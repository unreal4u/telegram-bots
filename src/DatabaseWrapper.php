<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use unreal4u\TelegramBots\Models\Configuration;
use unreal4u\TelegramBots\Models\Toolbox;

class DatabaseWrapper {
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Will initialize an EntityManager and return that
     *
     * @param string $entityNamespace
     * @return EntityManager
     * @throws \unreal4u\TelegramBots\Exceptions\Database\DriverNotFound
     */
    public function getEntity(string $entityNamespace): EntityManager
    {
        $finalConfiguration = $this->getFinalConfiguration();

        $toolbox = new Toolbox(false);
        $toolbox->setToolbox($finalConfiguration['mysql']['name'], [
            'driver' => $finalConfiguration['mysql']['driver'],
            'user' => $finalConfiguration['mysql']['dbuser'],
            'password' => $finalConfiguration['mysql']['dbpass'],
            'dbname' => $finalConfiguration['mysql']['dbname'],
            'charset' => $finalConfiguration['mysql']['charset'],
            'default_table_options' => $finalConfiguration['mysql']['default_table_options'],
        ], $finalConfiguration['mysql']['extra_types']);

        return $toolbox->getToolbox('mysqlStorage', $entityNamespace);
    }

    /**
     * Parses all configuration files and returns one final configuration
     *
     * TODO Do this more elegantly
     *
     * @return array
     */
    private function getFinalConfiguration(): array
    {
        $yamlConfFiles[] = Yaml::parse(file_get_contents('config/config.vagrant.yml'));
        if (file_exists('config/config-prod.vagrant.yml')) {
            $yamlConfFiles[] = Yaml::parse(file_get_contents('config/config-prod.vagrant.yml'));
        }
        $mainConfiguration = new Configuration();
        $processor = new Processor();
        return $processor->processConfiguration($mainConfiguration, $yamlConfFiles);
    }
}

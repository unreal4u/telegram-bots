<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use unreal4u\TelegramBots\Models\Configuration;
use unreal4u\TelegramBots\Models\Toolbox;

class DatabaseWrapper
{
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

        $toolbox = new Toolbox(false, $this->logger);
        $toolbox->setToolbox($finalConfiguration['default']['name'], [
            'driver' => $finalConfiguration['default']['driver'],
            'user' => $finalConfiguration['default']['dbuser'],
            'password' => $finalConfiguration['default']['dbpass'],
            'dbname' => $finalConfiguration['default']['dbname'],
            'path' => $finalConfiguration['default']['path'],
            'memory' => $finalConfiguration['default']['memory'],
            'charset' => $finalConfiguration['default']['charset'],
            'default_table_options' => $finalConfiguration['default']['default_table_options'],
        ], $finalConfiguration['default']['extra_types']);

        $this->logger->debug('Created configuration for environment', [
            'ENV' => ENVIRONMENT,
            'driver' => $finalConfiguration['default']['driver'],
        ]);

        return $toolbox->getToolbox('storage', $entityNamespace);
    }

    /**
     * Parses all configuration files and returns one final configuration
     *
     * @return array
     */
    private function getFinalConfiguration(): array
    {
        // Always include the base config
        $yamlConfFiles[] = Yaml::parse(file_get_contents('config/config.main.yml'));
        // Overwrite values on any environment we may have defined
        if (file_exists('config/config.' . ENVIRONMENT . '.yml')) {
            $this->logger->debug('Parsing additional yml file', ['ENV' => ENVIRONMENT]);
            $yamlConfFiles[] = Yaml::parse(file_get_contents('config/config.' . ENVIRONMENT . '.yml'));
        }

        $mainConfiguration = new Configuration();
        $processor = new Processor();
        return $processor->processConfiguration($mainConfiguration, $yamlConfFiles);
    }
}

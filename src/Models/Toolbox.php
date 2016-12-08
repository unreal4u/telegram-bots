<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models;

use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration as DoctrineConfiguration;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use unreal4u\TelegramBots\Exceptions\Database\DriverAlreadyDefined;
use unreal4u\TelegramBots\Exceptions\Database\DriverNotFound;

class Toolbox
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    private $developmentMode = false;

    private $toolbox = [];

    public function __construct(bool $developmentMode = false, LoggerInterface $logger)
    {
        $this->developmentMode = $developmentMode;

        if ($logger !== null) {
            $this->logger = $logger;
        }
    }

    /**
     * @param string $name
     * @param string $entityNamespace
     * @return EntityManager
     * @throws DriverNotFound
     */
    public function getToolbox(string $name, string $entityNamespace): EntityManager
    {
        if (!array_key_exists($name, $this->toolbox)) {
            throw new DriverNotFound(sprintf('The driver "%s" could not be found, call setToolbox first', $name));
        }

        if (empty($this->toolbox[$name]['initialized'])) {
            $this->toolbox[$name]['entityManager'] = $this->initializeDriver($name, $entityNamespace);
        }

        return $this->toolbox[$name]['entityManager'];
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return boolean
     */
    public function setToolbox(string $name, array $parameters, array $types=[]): bool
    {
        if ($this->toolboxAlreadyDefined($name) === false) {
            $this->createEmptyToolbox($name);
            $this->toolbox[$name]['parameters'] = $parameters;
            if (!empty($types)) {
                $this->toolbox[$name]['types'] = $types;
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @return Toolbox
     */
    private function createEmptyToolbox(string $name): Toolbox
    {
        $this->toolbox[$name] = [
            'parameters' => [],
            'types' => [],
            'initialized' => false,
            'entityManager' => null,
        ];

        return $this;
    }

    /**
     * @param string $name
     * @throws DriverAlreadyDefined
     * @return bool
     */
    private function toolboxAlreadyDefined(string $name): bool
    {
        if (array_key_exists($name, $this->toolbox)) {
            throw new DriverAlreadyDefined(
                sprintf('The toolbox named "%s" is already defined, please choose another name', $name)
            );
        }

        return false;
    }

    /**
     * @param string $name
     * @param string $entityNamespace
     * @return EntityManager
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    private function initializeDriver(string $name, string $entityNamespace): EntityManager
    {
        $driverSettings = $this->toolbox[$name];

        foreach ($driverSettings['types'] as $type => $className) {
            if (!Type::hasType($type)) {
                Type::addType($type, $className);
            }
        }

        $redis = new \Redis();
        $redis->connect('127.0.0.1');

        $cacheImplementation = new RedisCache();
        $cacheImplementation->setRedis($redis);
        $cacheImplementation->setNamespace($entityNamespace);

        $configuration = new DoctrineConfiguration();
        $configuration->addEntityNamespace($entityNamespace, __NAMESPACE__.'\\Entities');
        $annotationDriver = $configuration->newDefaultAnnotationDriver(__DIR__.'/Entities/');

        $configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

        $configuration->setProxyDir('/tmp/');
        $configuration->setProxyNamespace($entityNamespace);
        $configuration->setMetadataCacheImpl($cacheImplementation);
        $configuration->setQueryCacheImpl($cacheImplementation);
        $configuration->setResultCacheImpl($cacheImplementation);
        $configuration->setMetadataDriverImpl($annotationDriver);

        $this->toolbox[$name]['initialized'] = true;

        $entityManager = EntityManager::create($this->toolbox[$name]['parameters'], $configuration);
        return $entityManager;
    }
}

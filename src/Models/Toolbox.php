<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models;

use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration as DoctrineConfiguration;
use Doctrine\ORM\EntityManager;
use unreal4u\TelegramBots\Exceptions\Database\DriverAlreadyDefined;
use unreal4u\TelegramBots\Exceptions\Database\DriverNotFound;

class Toolbox
{
    private $developmentMode = false;

    private $toolbox = [];

    public function __construct(bool $developmentMode = false)
    {
        $this->developmentMode = $developmentMode;
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
     * @TODO find out why this is so damn slow on my environment
     *
     * @param string $name
     * @param string $entityNamespace
     * @return EntityManager
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    private function initializeDriver(string $name, string $entityNamespace): EntityManager
    {
        $driverSettings = $this->toolbox[$name];

        ##$beginTime = microtime(true);
        foreach ($driverSettings['types'] as $type => $className) {
            Type::addType($type, $className);
        }
        ##var_dump('Begin: '.(string)(microtime(true) - $beginTime));

        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        ##var_dump('R1: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until R1: 0.02060

        $cacheImplementation = new RedisCache();
        $cacheImplementation->setRedis($redis);
        $cacheImplementation->setNamespace($entityNamespace);
        ##var_dump('R2: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until R2: 0.11683

        $configuration = new DoctrineConfiguration();
        $configuration->addEntityNamespace($entityNamespace, __NAMESPACE__.'\\Entities');

        $configuration->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
        ##var_dump('R3: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until R3: 0.15823

        $configuration->setProxyDir('/tmp/');
        $configuration->setProxyNamespace($entityNamespace);
        $configuration->setMetadataCacheImpl($cacheImplementation);
        $configuration->setQueryCacheImpl($cacheImplementation);
        $configuration->setResultCacheImpl($cacheImplementation);
        ##var_dump('A: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until A: 0.15827

        // This one is the problematic
        #AnnotationRegistry::registerFile('vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
        #$reader = new SimpleAnnotationReader();
        #$reader->addNamespace('Doctrine\ORM\Mapping');
        #$cachedReader = new CachedReader($reader, $cacheImplementation);
        #$annotationDriver = new AnnotationDriver($cachedReader, [__DIR__.'/Entities']);

        $annotationDriver = $configuration->newDefaultAnnotationDriver(__DIR__.'/Entities/');
        ##var_dump('C: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until C: 1.29277

        $configuration->setMetadataDriverImpl($annotationDriver);
        ##var_dump('Mid: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until Mid: 1.29281

        $this->toolbox[$name]['initialized'] = true;

        $entityManager = EntityManager::create($this->toolbox[$name]['parameters'], $configuration);
        ##var_dump('Final: '.(string)(microtime(true) - $beginTime));
        # Typical time taken up until Final: 1.64584
        return $entityManager;
    }
}

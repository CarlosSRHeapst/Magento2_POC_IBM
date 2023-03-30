<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Config\App\Config\Type;

use Magento\Framework\App\Config\ConfigSourceInterface;
use Magento\Framework\App\Config\ConfigTypeInterface;
use Magento\Framework\App\Config\Spi\PostProcessorInterface;
use Magento\Framework\App\Config\Spi\PreProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Config\App\Config\Type\System\Reader;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Cache\LockGuardedCacheLoader;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Config\Processor\Fallback;
use Magento\Framework\Encryption\Encryptor;
use Magento\Store\Model\ScopeInterface as StoreScope;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\Type\Config;

/**
 * System configuration type
 *
 * @api
 * @since 100.1.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class System implements ConfigTypeInterface
{
    /**
     * Config cache tag.
     */
    const CACHE_TAG = 'config_scopes';

    /**
     * System config type.
     */
    const CONFIG_TYPE = 'system';

    /**
     * @var string
     */
    private static $lockName = 'SYSTEM_CONFIG';

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var PostProcessorInterface
     */
    private $postProcessor;

    /**
     * @var FrontendInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * The type of config.
     *
     * @var string
     */
    private $configType;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * List of scopes that were retrieved from configuration storage
     *
     * Is used to make sure that we don't try to load non-existing configuration scopes.
     *
     * @var array
     */
    private $availableDataScopes;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var LockGuardedCacheLoader
     */
    private $lockQuery;

    /**
     * @var StateInterface
     */
    private $cacheState;

    /**
     * System constructor.
     * @param ConfigSourceInterface $source
     * @param PostProcessorInterface $postProcessor
     * @param Fallback $fallback
     * @param FrontendInterface $cache
     * @param SerializerInterface $serializer
     * @param PreProcessorInterface $preProcessor
     * @param int $cachingNestedLevel
     * @param string $configType
     * @param Reader|null $reader
     * @param Encryptor|null $encryptor
     * @param LockManagerInterface|null $locker
     * @param LockGuardedCacheLoader|null $lockQuery
     * @param StateInterface|null $cacheState
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigSourceInterface $source,
        PostProcessorInterface $postProcessor,
        Fallback $fallback,
        FrontendInterface $cache,
        SerializerInterface $serializer,
        PreProcessorInterface $preProcessor,
        $cachingNestedLevel = 1,
        $configType = self::CONFIG_TYPE,
        Reader $reader = null,
        Encryptor $encryptor = null,
        LockManagerInterface $locker = null,
        LockGuardedCacheLoader $lockQuery = null,
        StateInterface $cacheState = null
    ) {
        $this->postProcessor = $postProcessor;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->configType = $configType;
        $this->reader = $reader ?: ObjectManager::getInstance()->get(Reader::class);
        $this->encryptor = $encryptor
            ?: ObjectManager::getInstance()->get(Encryptor::class);
        $this->lockQuery = $lockQuery
            ?: ObjectManager::getInstance()->get(LockGuardedCacheLoader::class);
        $this->cacheState = $cacheState
            ?: ObjectManager::getInstance()->get(StateInterface::class);
    }

    /**
     * Get configuration value by path
     *
     * System configuration is separated by scopes (default, websites, stores). Configuration of a scope is inherited
     * from its parent scope (store inherits website).
     *
     * Because there can be many scopes on single instance of application, the configuration data can be pretty large,
     * so it does not make sense to load all of it on every application request. That is why we cache configuration
     * data by scope and only load configuration scope when a value from that scope is requested.
     *
     * Possible path values:
     * '' - will return whole system configuration (default scope + all other scopes)
     * 'default' - will return all default scope configuration values
     * '{scopeType}' - will return data from all scopes of a specified {scopeType} (websites, stores)
     * '{scopeType}/{scopeCode}' - will return data for all values of the scope specified by {scopeCode} and scope type
     * '{scopeType}/{scopeCode}/some/config/variable' - will return value of the config variable in the specified scope
     *
     * @inheritdoc
     * @since 100.1.2
     */
    public function get($path = '')
    {
        //var_dump($configType);
        //string(6) "system"
        //string(6) "system"

        //var_dump($path);
        //string(33) "default/admin/url/use_custom_path"
        //string(40) "default/newrelicreporting/general/enable"

/////////////////////////////////////////////////////////////////////////////////////////////

        //14/03/2023 Nueva investigacion system y path

        //Desde framework/App/Config.php

         /*
                string(25) "default/web/url/use_store"
                string(37) "Magento\Config\App\Config\Type\System"
                array(3) {
                [0]=>
                string(11) "__construct"
                [1]=>
                string(3) "get"
                [2]=>
                string(5) "clean"
                }
                array(0) {
                }

        */
        //echo $path . " System";
        //var_dump($path);

        if ($path === '') {
            $this->data = array_replace_recursive($this->loadAllData(), $this->data);

            return $this->data;
        }
        //DB2MOD investigación
        //"default/admin/url/use_custom_path"
        //var_dump($path);
        return $this->getWithParts($path);
    }

    /**
     * Proceed with parts extraction from path.
     *
     * @param string $path
     * @return array|int|string|boolean
     */
    private function getWithParts($path)
    {
        //var_dump($path);
        //string(33) "default/admin/url/use_custom_path"
        //string(40) "default/newrelicreporting/general/enable"

/////////////////////////////////////////////////////////////////////////////////////////////

        //14/03/2023 Nueva investigacion system y path
        //echo $path . " System ";
        //default/web/url/use_store 

        $pathParts = explode('/', $path);

        //var_dump($pathParts);

        if (count($pathParts) === 1 && $pathParts[0] !== ScopeInterface::SCOPE_DEFAULT) {
            if (!isset($this->data[$pathParts[0]])) {
                $data = $this->readData();
                $this->data = array_replace_recursive($data, $this->data);
            }

            return $this->data[$pathParts[0]];
        }

        $scopeType = array_shift($pathParts);

        //DB2MOD investigación

        //echo $path . " System "; //default/web/url/use_store 
        //var_dump($scopeType); //default

        if ($scopeType === ScopeInterface::SCOPE_DEFAULT) {
            if (!isset($this->data[$scopeType])) {
                //DB2MOD
                //NUEVA PRUEBA EN 14/03/2023
                //var_dump($scopeType); //DEFAULT


                //AQUI SI HAY DEFAULT
                //14/03/2023 Parece que aqui al asignar data esta el error cuando $path == //default/web/url/use_store 

                //var_dump(gettype($this->data)); // es array al iniciar
                //var_dump($this->data); //array vacio
                /* 
                    array(0) {
                    }

                */
                
                //POSIBLE ERROR AQUI!?
                //var_dump($this->loadDefaultScopeData($scopeType));


                $this->data = array_replace_recursive($this->loadDefaultScopeData($scopeType), $this->data);
                
                
                //echo "Si pase";
                //NUEVA PRUEBA EN 14/03/2023 es importante ver que obtiene DATA
                //var_dump(gettype($this->data));
                /*var_dump(get_class($this->data));
                var_dump(get_class_methods($this->types["system"]));
                var_dump(get_object_vars($this->types["system"]));*/
            }

            return $this->getDataByPathParts($this->data[$scopeType], $pathParts);
        }

        $scopeId = array_shift($pathParts);

        if (!isset($this->data[$scopeType][$scopeId])) {
            $scopeData = $this->loadScopeData($scopeType, $scopeId);

            if (!isset($this->data[$scopeType][$scopeId])) {
                $this->data = array_replace_recursive($scopeData, $this->data);
            }
        }

        return isset($this->data[$scopeType][$scopeId])
            ? $this->getDataByPathParts($this->data[$scopeType][$scopeId], $pathParts)
            : null;
    }

    /**
     * Load configuration data for all scopes.
     *
     * @return array
     */
    private function loadAllData()
    {
        if (!$this->cacheState->isEnabled(Config::TYPE_IDENTIFIER)) {
            return $this->readData();
        }

        $loadAction = function () {
            $cachedData = $this->cache->load($this->configType);
            $data = false;
            if ($cachedData !== false) {
                $data = $this->serializer->unserialize($this->encryptor->decrypt($cachedData));
            }
            return $data;
        };

        return $this->lockQuery->lockedLoadData(
            self::$lockName,
            $loadAction,
            \Closure::fromCallable([$this, 'readData']),
            \Closure::fromCallable([$this, 'cacheData'])
        );
    }

    /**
     * Load configuration data for default scope.
     *
     * @param string $scopeType
     * @return array
     */
    private function loadDefaultScopeData($scopeType)
    {

        //Apunto a string(39) "Magento\Framework\App\Cache\Type\Config"
        /*var_dump(get_class($this->cache));
        var_dump(get_class_methods($this->cache));
        var_dump(get_object_vars($this->cache));*/

        //var_dump($scopeType); //default

        //14/03/2023
        //echo $scopeType . " SYSTEM_DB2MOD ";

        if (!$this->cacheState->isEnabled(Config::TYPE_IDENTIFIER)) {
            return $this->readData();
        }
        //DB2MOD 08/02/2023
        $loadAction = function () use ($scopeType) {
//////////////////////////////////////////////////////////////////////////////////////////////////
            //var_dump($this->configType . '_' . $scopeType); //system_default
            /****************PRUEBA PARA AVERIGUAR STACK TRACE******************** */
            /*$arr = [];
            $cachedData = $this->cache->load($arr);*/
            //TypeError: strtoupper(): Argument #1 ($string) must be of type string, array given 
            //in /var/www/html/magento2/vendor/magento/framework/Cache/Frontend/Adapter/Zend.php:123
//////////////////////////////////////////////////////////////////////////////////////////////////
            //14/03/2023

            /*var_dump($this->configType . '_' . $scopeType); //system_default
            $cachedData = $this->cache->load("SYSTEM_DEFAULT");
            var_dump($cachedData);*/

            $cachedData = $this->cache->load($this->configType . '_' . $scopeType);

 ///        //Ahora SYSTEM_DEFAULT y system_default regresan false.
            //var_dump($cachedData);


//////////////////////////////////////////////////////////////////////////////////////////////////
            //var_dump($scopeType); //default;
            //var_dump($cachedData);
///////////////////////////////////////////////////////////////////////////////////////////////////
            //14/03/2023
            //var_dump($scopeType); //default;
            //var_dump($cachedData); //false

            $scopeData = false;
            if ($cachedData !== false) {
                $scopeData = [$scopeType => $this->serializer->unserialize($this->encryptor->decrypt($cachedData))];
            }
            /*else{

                $scopeData2 = [$scopeType => $this->serializer->unserialize($this->encryptor->decrypt($cachedData))];
                var_dump($scopeData2);
                sa;
                //14/03/2023 Unable to unserialize value. 
                //DB2MOD esto falla
            }*/
            //var_dump($scopeData); //false
            return $scopeData;
        };

        /*var_dump(get_class($loadAction));
        var_dump(get_class_methods($loadAction));
        var_dump(get_object_vars($loadAction));*/

        //14/03/2023 MISMA PRUEBA DE LOADACTION
        /* 
            string(7) "Closure"
            array(4) {
            [0]=>
            string(4) "bind"
            [1]=>
            string(6) "bindTo"
            [2]=>
            string(4) "call"
            [3]=>
            string(12) "fromCallable"
            }
            array(0) {
            }
        */

        /*$varTest = $this->lockQuery->lockedLoadData(
            self::$lockName,
            $loadAction,
            \Closure::fromCallable([$this, 'readData']),
            \Closure::fromCallable([$this, 'cacheData'])
        );

        var_dump($varTest);*/

        /*var_dump(get_class($this->lockQuery));
        var_dump(get_class_methods($this->lockQuery));
        var_dump(get_object_vars($this->lockQuery));*/
        /*
            string(46) "Magento\Framework\Cache\LockGuardedCacheLoader"
            array(3) {
            [0]=>
            string(11) "__construct"
            [1]=>
            string(14) "lockedLoadData"
            [2]=>
            string(15) "lockedCleanData"
            }
            array(0) {
            }
        */

        //DB2MOD //14/03/2023 Nuevo problema......................................
        return $this->lockQuery->lockedLoadData(
            self::$lockName,
            $loadAction,
            \Closure::fromCallable([$this, 'readData']),
            \Closure::fromCallable([$this, 'cacheData'])
        );
    }

    /**
     * Load configuration data for a specified scope.
     *
     * @param string $scopeType
     * @param string $scopeId
     * @return array
     */
    private function loadScopeData($scopeType, $scopeId)
    {
        if (!$this->cacheState->isEnabled(Config::TYPE_IDENTIFIER)) {
            return $this->readData();
        }

        $loadAction = function () use ($scopeType, $scopeId) {
            $cachedData = $this->cache->load($this->configType . '_' . $scopeType . '_' . $scopeId);
            $scopeData = false;
            if ($cachedData === false) {
                if ($this->availableDataScopes === null) {
                    $cachedScopeData = $this->cache->load($this->configType . '_scopes');
                    if ($cachedScopeData !== false) {
                        $serializedCachedData = $this->encryptor->decrypt($cachedScopeData);
                        $this->availableDataScopes = $this->serializer->unserialize($serializedCachedData);
                    }
                }
                if (is_array($this->availableDataScopes) && !isset($this->availableDataScopes[$scopeType][$scopeId])) {
                    $scopeData = [$scopeType => [$scopeId => []]];
                }
            } else {
                $serializedCachedData = $this->encryptor->decrypt($cachedData);
                $scopeData = [$scopeType => [$scopeId => $this->serializer->unserialize($serializedCachedData)]];
            }

            return $scopeData;
        };

        return $this->lockQuery->lockedLoadData(
            self::$lockName,
            $loadAction,
            \Closure::fromCallable([$this, 'readData']),
            \Closure::fromCallable([$this, 'cacheData'])
        );
    }

    /**
     * Cache configuration data.
     *
     * Caches data per scope to avoid reading data for all scopes on every request
     *
     * @param array $data
     * @return void
     */
    private function cacheData(array $data)
    {
        $this->cache->save(
            $this->encryptor->encryptWithFastestAvailableAlgorithm($this->serializer->serialize($data)),
            $this->configType,
            [self::CACHE_TAG]
        );
        $this->cache->save(
            $this->encryptor->encryptWithFastestAvailableAlgorithm($this->serializer->serialize($data['default'])),
            $this->configType . '_default',
            [self::CACHE_TAG]
        );
        $scopes = [];
        foreach ([StoreScope::SCOPE_WEBSITES, StoreScope::SCOPE_STORES] as $curScopeType) {
            foreach ($data[$curScopeType] ?? [] as $curScopeId => $curScopeData) {
                $scopes[$curScopeType][$curScopeId] = 1;
                $this->cache->save(
                    $this->encryptor->encryptWithFastestAvailableAlgorithm($this->serializer->serialize($curScopeData)),
                    $this->configType . '_' . $curScopeType . '_' . $curScopeId,
                    [self::CACHE_TAG]
                );
            }
        }
        $this->cache->save(
            $this->encryptor->encryptWithFastestAvailableAlgorithm($this->serializer->serialize($scopes)),
            $this->configType . '_scopes',
            [self::CACHE_TAG]
        );
    }

    /**
     * Walk nested hash map by keys from $pathParts.
     *
     * @param array $data to walk in
     * @param array $pathParts keys path
     * @return mixed
     */
    private function getDataByPathParts($data, $pathParts)
    {
        foreach ($pathParts as $key) {
            if ((array)$data === $data && isset($data[$key])) {
                $data = $data[$key];
            } elseif ($data instanceof \Magento\Framework\DataObject) {
                $data = $data->getDataByKey($key);
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * The freshly read data.
     *
     * @return array
     */
    private function readData(): array
    {
        /*var_dump(get_class($this->data));
        var_dump(get_class_methods($this->data));
        var_dump(get_object_vars($this->data));*/

        //No es un objeto es un arreglo

        //var_dump(gettype($this->data)); //ES UN STRING?
        //var_dump($this->data); //
        /* 
            array(0) {
            }
        */
        $this->data = $this->reader->read();
        $this->data = $this->postProcessor->process(
            $this->data
        );

        return $this->data;
    }

    /**
     * Clean cache and global variables cache.
     *
     * Next items cleared:
     * - Internal property intended to store already loaded configuration data
     * - All records in cache storage tagged with CACHE_TAG
     *
     * @return void
     * @since 100.1.2
     */
    public function clean()
    {
        $this->data = [];
        $cleanAction = function () {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, [self::CACHE_TAG]);
        };

        if (!$this->cacheState->isEnabled(Config::TYPE_IDENTIFIER)) {
            return $cleanAction();
        }

        $this->lockQuery->lockedCleanData(
            self::$lockName,
            $cleanAction
        );
    }
}

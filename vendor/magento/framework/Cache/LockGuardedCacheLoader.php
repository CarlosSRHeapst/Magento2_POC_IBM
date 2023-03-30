<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Cache;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\App\DeploymentConfig;

/**
 * Default mutex that provide concurrent access to cache storage.
 */
class LockGuardedCacheLoader
{
    /**
     * @var LockManagerInterface
     */
    private $locker;

    /**
     * Lifetime of the lock for write in cache.
     *
     * Value of the variable in milliseconds.
     *
     * @var int
     */
    private $lockTimeout;

    /**
     * Timeout between retrieves to load the configuration from the cache.
     *
     * Value of the variable in milliseconds.
     *
     * @var int
     */
    private $delayTimeout;

    /**
     * Timeout for information to be collected and saved.
     * If timeout passed that means that data cannot be saved right now.
     * And we will just return collected data.
     *
     * Value of the variable in milliseconds.
     *
     * @var int
     */
    private $loadTimeout;

    /**
     * Minimal delay timeout in ms.
     *
     * @var int
     */
    private $minimalDelayTimeout;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * Option that allows to switch off blocking for parallel generation.
     *
     * @var string
     */
    private const CONFIG_NAME_ALLOW_PARALLEL_CACHE_GENERATION = 'allow_parallel_generation';

    /**
     * Config value of parallel generation.
     *
     * @var bool
     */
    private $allowParallelGenerationConfigValue;

    /**
     * LockGuardedCacheLoader constructor.
     * @param LockManagerInterface $locker
     * @param int $lockTimeout
     * @param int $delayTimeout
     * @param int $loadTimeout
     * @param int $minimalDelayTimeout
     * @param DeploymentConfig|null $deploymentConfig
     */
    public function __construct(
        LockManagerInterface $locker,
        int $lockTimeout = 10000,
        int $delayTimeout = 20,
        int $loadTimeout = 10000,
        int $minimalDelayTimeout = 5,
        DeploymentConfig $deploymentConfig = null
    ) {
        $this->locker = $locker;
        $this->lockTimeout = $lockTimeout;
        $this->delayTimeout = $delayTimeout;
        $this->loadTimeout = $loadTimeout;
        $this->minimalDelayTimeout = $minimalDelayTimeout;
        $this->deploymentConfig = $deploymentConfig ?? ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Load data.
     *
     * @param string $lockName
     * @param callable $dataLoader
     * @param callable $dataCollector
     * @param callable $dataSaver
     * @return mixed
     */
    public function lockedLoadData(
        string $lockName,
        callable $dataLoader,
        callable $dataCollector,
        callable $dataSaver
    ) {
        $cachedData = $dataLoader(); //optimistic read
        $deadline = microtime(true) + $this->loadTimeout / 1000;

        if (empty($this->allowParallelGenerationConfigValue)) {
            $cacheConfig = $this
                ->deploymentConfig
                ->getConfigData('cache');
            $this->allowParallelGenerationConfigValue = $cacheConfig[self::CONFIG_NAME_ALLOW_PARALLEL_CACHE_GENERATION]
                ?? false;
        }
        //var_dump($cachedData);
        while ($cachedData === false) {
            //echo " AntesODespues " . PHP_EOL;
            if ($deadline <= microtime(true)) {
                return $dataCollector();
            }

///////////////////////////////////////////////////////////////////////////////////////////////
            //echo " AntesODespues1 " . PHP_EOL;
            //var_dump($this->allowParallelGenerationConfigValue);
            if ($this->allowParallelGenerationConfigValue === true) {
                //echo " AntesODespues1A " . PHP_EOL;
                return $dataCollector();
            }else{
                //echo " AntesODespues1B " . PHP_EOL; //Se va por aqui
                ////////////////////////////////////////////////////////////////////////////
                /*var_dump(get_class($dataCollector));
                var_dump(get_class_methods($dataCollector));
                var_dump(get_object_vars($dataCollector));*/
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
                $data = $dataCollector();
                //16/03/2023 DB2MOD PROBLEMA CON dataCollector ???
                //Se manda llamar funcion anonima descrita en System readData y 
                //no se devuelve nada
                var_dump($data); //UNREACHABLE
                $dataSaver($data);
                $cachedData = $data;
                //echo " AntesODespues1B2 " . PHP_EOL; //Aqui hay error
            }
            //echo " AntesODespues2 " . PHP_EOL; //Aqui no Llega

/////////////////////////////////////////////////////////////////////////////////////////////

            //DB2MOD CODIGO ORIGINAL DE MYSQL
            /*if ($this->locker->lock($lockName, 0)) {
                try {
                    $data = $dataCollector();
                    $dataSaver($data);
                    $cachedData = $data;
                } finally {
                    $this->locker->unlock($lockName);
                }
            } elseif ($this->allowParallelGenerationConfigValue === true) {
                return $dataCollector();
            }*/
            
            if ($cachedData === false) {
                usleep($this->getLookupTimeout() * 1000);
                $cachedData = $dataLoader();
            }
            //echo " AntesODespues3 " . PHP_EOL;
        }
        //echo " AntesODespuesFIN " . PHP_EOL;
        return $cachedData;
    }

    /**
     * Clean data.
     *
     * @param string $lockName
     * @param callable $dataCleaner
     * @return void
     */
    public function lockedCleanData(string $lockName, callable $dataCleaner)
    {
        while ($this->locker->isLocked($lockName)) {
            usleep($this->getLookupTimeout() * 1000);
        }

        $dataCleaner();
    }

    /**
     * Delay will be applied as rand($minimalDelayTimeout, $delayTimeout).
     * This helps to desynchronize multiple clients trying
     * to acquire the lock for the same resource at the same time
     *
     * @return int
     */
    private function getLookupTimeout()
    {
        return rand($this->minimalDelayTimeout, $this->delayTimeout);
    }
}

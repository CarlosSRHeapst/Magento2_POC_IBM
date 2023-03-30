<?php
/**
 * Magento object manager. Responsible for instantiating objects taking into account:
 * - constructor arguments (using configured, and provided parameters)
 * - class instances life style (singleton, transient)
 * - interface preferences
 *
 * Intentionally contains multiple concerns for best performance
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ObjectManager;

class ObjectManager implements \Magento\Framework\ObjectManagerInterface
{
    /**
     * @var \Magento\Framework\ObjectManager\FactoryInterface
     */
    protected $_factory;

    /**
     * List of shared instances
     *
     * @var array
     */
    protected $_sharedInstances = [];

    /**
     * @var ConfigInterface
     */
    protected $_config;

    /**
     * @param FactoryInterface $factory
     * @param ConfigInterface $config
     * @param array &$sharedInstances
     */
    public function __construct(FactoryInterface $factory, ConfigInterface $config, &$sharedInstances = [])
    {
        $this->_config = $config;
        /*$variableaTirar = gettype($config);
        var_dump($variableaTirar);*/
        $this->_factory = $factory;
        $this->_sharedInstances = &$sharedInstances;
        $this->_sharedInstances[\Magento\Framework\ObjectManagerInterface::class] = $this;
    }

    /**
     * Create new object instance
     *
     * @param string $type
     * @param array $arguments
     * @return mixed
     */
    public function create($type, array $arguments = [])
    {
        //DB2MOD Por aqui sigue (PUNTO FINAL?)
        //var_dump($this->_config->getPreference($type));
        if($this->_config->getPreference($type) == "Magento\Store\Model\Group\Interceptor")
        {
            //var_dump($arguments);
            //sa;
            //ARGUMENTOS VIENEN COMO ARREGLO VACIO PARA EL INTERCEPTOR DE GROUP
        }
        //var_dump($arguments);
        return $this->_factory->create($this->_config->getPreference($type), $arguments);
    }

    /**
     * Retrieve cached object instance
     *
     * @param string $type
     * @return mixed
     */
    public function get($type)
    {
        $type = ltrim($type, '\\');
        $type = $this->_config->getPreference($type);
        if (!isset($this->_sharedInstances[$type])) {
            $this->_sharedInstances[$type] = $this->_factory->create($type);
        }
        return $this->_sharedInstances[$type];
    }

    /**
     * Configure di instance
     * Note: All arguments should be pre-processed (sort order, translations, etc) before passing to method configure.
     *
     * @param array $configuration
     * @return void
     */
    public function configure(array $configuration)
    {
        //var_dump($configuration);
        //print_r($configuration);
        //echo "EOL_EOL" . PHP_EOL;
        $this->_config->extend($configuration);
    }
}

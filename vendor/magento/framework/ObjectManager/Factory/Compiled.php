<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ObjectManager\Factory;

class Compiled extends AbstractFactory
{
    /**
     * Object manager config
     *
     * @var \Magento\Framework\ObjectManager\ConfigInterface
     */
    protected $config;

    /**
     * Global arguments
     *
     * @var array
     */
    protected $globalArguments;

    /**
     * @var array
     */
    private $sharedInstances;

    /**
     * @param \Magento\Framework\ObjectManager\ConfigInterface $config
     * @param array $sharedInstances
     * @param array $globalArguments
     */
    public function __construct(
        \Magento\Framework\ObjectManager\ConfigInterface $config,
        &$sharedInstances = [],
        $globalArguments = []
    ) {
        $this->config = $config;
        $this->globalArguments = $globalArguments;
        $this->sharedInstances = &$sharedInstances;
    }

    /**
     * Create instance with call time arguments
     *
     * @param string $requestedType
     * @param array $arguments
     * @return object
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create($requestedType, array $arguments = [])
    {
        //DB2MOD Obtiene las instancias de las clases a generar (Aqui hay un MySql que hay que reemplazar)
        //Este tampoco es el origen

        
        //DB2MOD Pruebas clase
        //var_dump($this->config->getArguments("Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory"));
        //echo $requestedType . PHP_EOL;
        $args = $this->config->getArguments($requestedType);
        //var_dump($args["instanceName"]);
        $type = $this->config->getInstanceType($requestedType);
        //echo $type . PHP_EOL;

        if ($args === []) {
            // Case 1: no arguments required
            return new $type();
        } elseif ($args !== null) {
            /**
             * Case 2: arguments retrieved from pre-compiled DI cache
             *
             * Argument key meanings:
             *
             * _i_: shared instance of a class or interface
             * _ins_: non-shared instance of a class or interface
             * _v_: non-array literal value
             * _vac_: array, may be nested and contain other types of keys listed here (objects, array, nulls, etc)
             * _vn_: null value
             * _a_: value to be taken from named environment variable
             * _d_: default value in case environment variable specified by _a_ does not exist
             */
            //Entra sencillo el arreglo
            foreach ($args as $key => &$argument) {
                if (isset($arguments[$key])) {
                    $argument = $arguments[$key];
                } elseif (isset($argument['_i_'])) {
                    $argument = $this->get($argument['_i_']);
                } elseif (isset($argument['_ins_'])) {
                    $argument = $this->create($argument['_ins_']);
                } elseif (isset($argument['_v_'])) {
                    $argument = $argument['_v_'];
                } elseif (isset($argument['_vac_'])) {
                    $argument = $argument['_vac_'];
                    $this->parseArray($argument);
                } elseif (isset($argument['_vn_'])) {
                    $argument = null;
                } elseif (isset($argument['_a_'])) {
                    if (isset($this->globalArguments[$argument['_a_']])) {
                        $argument = $this->globalArguments[$argument['_a_']];
                    } else {
                        $argument = $argument['_d_'];
                    }
                }
            }
            $args = array_values($args);
        } else {
            // Case 3: arguments retrieved in runtime
            $parameters = $this->getDefinitions()->getParameters($type) ?: [];
            $args = $this->resolveArgumentsInRuntime(
                $type,
                $parameters,
                $arguments
            );
        }
        //Sale "complejo"
        /*if($type == "Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory")
        {
            var_dump($args[0]);
            echo "Entre aca" . PHP_EOL;
        }*/

        //15/02/2023 investigacion StoreResolver

        //13/02/2023 investigacion error 
        /*
                    Type Error occurred when creating object: Magento\Framework\Locale\Resolver  
                    , Magento\Framework\App\Config\PreProcessorComposite::process(): Argument #  
                    1 ($config) must be of type array, null given, called in /var/www/html/mage  
                    nto2/vendor/magento/module-config/App/Config/Type/System/Reader.php on line  
                    76          
        */

        /*if($type == "Magento\Framework\Locale\Resolver")
        {
            echo "Entre aca" . PHP_EOL;
        }*/
        return $this->createObject($type, $args);
    }

    /**
     * Parse array argument
     *
     * @param array $array
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function parseArray(&$array)
    {
        foreach ($array as $key => &$argument) {
            if ($argument === (array)$argument) {
                if (isset($argument['_i_'])) {
                    $argument = $this->get($argument['_i_']);
                } elseif (isset($argument['_ins_'])) {
                    $argument = $this->create($argument['_ins_']);
                } elseif (isset($argument['_a_'])) {
                    if (isset($this->globalArguments[$argument['_a_']])) {
                        $argument = $this->globalArguments[$argument['_a_']];
                    } else {
                        $argument = $argument['_d_'];
                    }
                } else {
                    $this->parseArray($argument);
                }
            }
        }
    }

    /**
     * Retrieve cached object instance
     *
     * @param string $type
     * @return mixed
     */
    protected function get($type)
    {
        if (!isset($this->sharedInstances[$type])) {
            $this->sharedInstances[$type] = $this->create($type);
        }
        return $this->sharedInstances[$type];
    }
}

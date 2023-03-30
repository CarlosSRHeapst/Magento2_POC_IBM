<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Config\App\Config\Source;

use Magento\Framework\App\Config\ConfigSourceInterface;
use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Scope\Converter;
use Magento\Framework\DB\Adapter\TableNotFoundException;

/**
 * Class for retrieving runtime configuration from database.
 *
 * @api
 * @since 100.1.2
 */
class RuntimeConfigSource implements ConfigSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var ScopeCodeResolver
     */
    private $scopeCodeResolver;
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param Converter $converter
     * @param DeploymentConfig|null $deploymentConfig
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ScopeCodeResolver $scopeCodeResolver,
        Converter $converter,
        ?DeploymentConfig $deploymentConfig = null
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->converter = $converter;
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->deploymentConfig = $deploymentConfig ?? ObjectManager::getInstance()->get(DeploymentConfig::class);
    }

    /**
     * Get initial data.
     *
     * @param string $path Format is scope type and scope code separated by slash: e.g. "type/code"
     * @return array
     * @since 100.1.2
     */
    public function get($path = '')
    {
        //DB2MOD PRUEBAS DE DATA (Anterior)
        //$this->deploymentConfig->isDbAvailable() es true loadconfig falla por collection no tiene Scope

        //23/03/2023
        //var_dump($path);

        //21/03/2023 Revisar Posible error con DataObject

        //var_dump($this->deploymentConfig->isDbAvailable()); //true
        //var_dump($this->loadConfig()); //objeto--

        $data = new DataObject($this->deploymentConfig->isDbAvailable() ? $this->loadConfig() : []);
        //var_dump($data); //Tiene datos   

        /*var_dump(get_class($data));
        var_dump(get_class_methods($data));
        var_dump(get_object_vars($data));*/

        /* 
            string(28) "Magento\Framework\DataObject"
            array(25) {
            [0]=>
            string(11) "__construct"
            [1]=>
            string(7) "addData"
            [2]=>
            string(7) "setData"
            [3]=>
            string(9) "unsetData"
            [4]=>
            string(7) "getData"
            [5]=>
            string(13) "getDataByPath"
            [6]=>
            string(12) "getDataByKey"
            [7]=>
            string(18) "setDataUsingMethod"
            [8]=>
            string(18) "getDataUsingMethod"
            [9]=>
            string(7) "hasData"
            [10]=>
            string(7) "toArray"
            [11]=>
            string(14) "convertToArray"
            [12]=>
            string(5) "toXml"
            [13]=>
            string(12) "convertToXml"
            [14]=>
            string(6) "toJson"
            [15]=>
            string(13) "convertToJson"
            [16]=>
            string(8) "toString"
            [17]=>
            string(6) "__call"
            [18]=>
            string(7) "isEmpty"
            [19]=>
            string(9) "serialize"
            [20]=>
            string(5) "debug"
            [21]=>
            string(9) "offsetSet"
            [22]=>
            string(12) "offsetExists"
            [23]=>
            string(11) "offsetUnset"
            [24]=>
            string(9) "offsetGet"
            }
            array(0) {
            }
        */

        //var_dump($data->getData($path));
        //23/03/2023
        /*PRUEBA PARA CONFIGURACIÓN*/
        //echo " Estoy aca y la key que mando es: " . $path . PHP_EOL;
        //$dataTest = $data->getData("CONFIG_ID"); //NULL
        //var_dump($data); //Se recupera en archivo

        //$dataTest = $data->getData("default"); //Resultados
        //var_dump($dataTest);

        //23/03/2023 FIX Y PRUEBAS
        //if($path == ""){
            //echo " Cambiado " . PHP_EOL;
            //$path = "ALL_OBJ"; //Tiene que ser un nivel mas arriba de default, se prueba con /
            //Propone "ALL_OBJ" para que retorne todo el valor, se investigara si hay mas casos asi
        //}
        

        return $data->getData($path) !== null ? $data->getData($path) : null;
    }

    /**
     * Load config from database.
     *
     * Load collection from db and presents it in array with path keys, like:
     * * scope/key/key *
     *
     * @return array
     */
    private function loadConfig()
    {
        try {
            $collection = $this->collectionFactory->create();
            //DB2MOD 19 ENE Identificado donde tiene que cargar la info.
            $collection->load();
        } catch (\DomainException $e) {
            $collection = [];
        } catch (TableNotFoundException $exception) {
            // database is empty or not setup
            $collection = [];
        }
        $config = [];
        //Magento\Framework\App\ScopeDefault
        foreach ($collection as $item) {

            //DB2MOD
            /*if (property_exists($item, 'getScope')) 
            {
                echo "Existe " . PHP_EOL;
            }else{
                echo "No existe " . PHP_EOL;
            }

            var_dump(get_class($item));
            var_dump(get_class_methods($item));

            sa;*/
            if ($item->getScope() === ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
                $config[$item->getScope()][$item->getPath()] = $item->getValue();
            } else {
                //Resolve entra con valores nulos, no se procesan 

                //var_dump(get_class($item));
                //var_dump(get_class_methods($item));

                $code = $this->scopeCodeResolver->resolve($item->getScope(), $item->getScopeId());
                //En esta linea ocurren dos errores en el compilador no se detecta "code" como un valor valido (ya que es nulo)
                //Y en el navegador presenta el error de 
                //InvalidArgumentException: Invalid scope type '' in /var/www/html/magento2/vendor/magento/framework/App/ScopeResolverPool.php:44
                $config[$item->getScope()][$code][$item->getPath()] = $item->getValue();
            }
        }

        foreach ($config as $scope => &$item) {
            if ($scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
                $item = $this->converter->convert($item);
            } else {
                foreach ($item as &$scopeItems) {
                    $scopeItems = $this->converter->convert($scopeItems);
                }
            }
        }
        return $config;
    }
}

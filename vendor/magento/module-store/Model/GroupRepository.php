<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Store\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config;

/**
 * Information Expert in store groups handling
 *
 * @package Magento\Store\Model
 */
class GroupRepository implements \Magento\Store\Api\GroupRepositoryInterface
{
    /**
     * @var GroupFactory
     */
    protected $groupFactory;

    /**
     * @var \Magento\Store\Api\Data\GroupInterface[]
     */
    protected $entities = [];

    /**
     * @var bool
     */
    protected $allLoaded = false;

    /**
     * @var \Magento\Store\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var Config
     */
    private $appConfig;

    /**
     * @param GroupFactory $groupFactory
     * @param \Magento\Store\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        GroupFactory $groupFactory,
        \Magento\Store\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
    ) {
        $this->groupFactory = $groupFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        //DB2MOD 15/02/2023 posible falla en tercer intento por ID nulo
        //ID_NULL
        //var_dump($id);
        //sa; //se coloca esta linea para sacar stacktrace hacia cli
        if (isset($this->entities[$id])) {
            return $this->entities[$id];
        }

        $group = $this->groupFactory->create([
            'data' => $this->getAppConfig()->get('scopes', "groups/$id", [])
        ]);

        //DB2MOD
        /*if (property_exists($item, 'getScope')) 
            {
                echo "Existe " . PHP_EOL;
            }else{
                echo "No existe " . PHP_EOL;
            }
        */

        //21/02/2023 DB2MOD INTERCEPTOR GROUPREP
        //DB2MOD este objeto trae un arreglo vacio
        /*$varObj = $this->getAppConfig()->get('scopes', "groups/$id", []);
        var_dump($varObj);*/
        //sa;

        
        //DB2MOD este objeto trae un arreglo vacio
        /*$varObj = $this->getAppConfig();
        var_dump(get_class($varObj));
        var_dump(get_class_methods($varObj));*/

        //var_dump(get_class($group));
        //var_dump(get_class_methods($group));
        //var_dump(get_class_vars($group));

        //Magento\Store\Model\Group\Interceptor->getId();

        //var_dump($group); //Muy grande para analizar, 
        //libera muchas clases, configuraciones e interceptores
        //DB2MOD ERROR DESCONOCIDO 14/02/2023

        /*15/02/2023 Se reviso stacktrace completo, 
        los objetos si llevan esta secuencia
        se debe enviar una SCOPE_ID nulo, se provede a revisar 
        secuencia de Interceptores (Metodos y Creaciones)
        */
        //Magento\Store\Model\Group\Interceptor->getId();
        //var_dump($group->getId()); //NULL //21/02/2023 sigue siendo NULL
        //21/02/2023
        /*
        GROUP ID MUESTRA (PERO SIGUE TRAYENDO NULL)

        string(1) "0"
        string(1) "0"
        string(1) "1"
        string(1) "1"
        string(1) "1"
        */

        //21/02/2023 POSIBLE PROBLEMA CON EL OBJETO GROUP
        //var_dump($group->getId()); //Pruebas volviendo el valor 0
        if (null === $group->getId()) {
            throw new NoSuchEntityException();
        }
        $this->entities[$id] = $group;
        return $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        if (!$this->allLoaded) {
            $groups = $this->getAppConfig()->get('scopes', 'groups', []);
            foreach ($groups as $data) {
                $group = $this->groupFactory->create([
                    'data' => $data
                ]);
                $this->entities[$group->getId()] = $group;
            }
            $this->allLoaded = true;
        }

        return $this->entities;
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->entities = [];
        $this->allLoaded = false;
    }

    /**
     * Retrieve application config.
     *
     * @deprecated 100.1.3
     * @return Config
     */
    private function getAppConfig()
    {
        if (!$this->appConfig) {
            $this->appConfig = ObjectManager::getInstance()->get(Config::class);
        }
        return $this->appConfig;
    }
}

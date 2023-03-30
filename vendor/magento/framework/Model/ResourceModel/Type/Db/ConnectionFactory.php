<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Model\ResourceModel\Type\Db;

use Magento\Framework\ObjectManagerInterface;

/**
 * Connection adapter factory
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $connectionConfig)
    {
        //DB2MOD AQUI SE ALIMENTAN LOS SETTING DE CONFIGURACION DEL ENV.
        /*
        array(9) {
            ["host"]=>
            string(15) "localhost:50000"
            ["dbname"]=>
            string(6) "testdb"
            ["username"]=>
            string(8) "db2inst1"
            ["password"]=>
            string(6) "123456"
            ["model"]=>
            string(3) "db2"
            ["engine"]=>
            string(0) ""
            ["initStatements"]=>
            string(0) ""
            ["active"]=>
            string(1) "1"
            ["driver_options"]=>
            array(0) {
            }
            }
        */
        //var_dump($this->objectManager);
        /** @var \Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface $adapterInstance */
        $adapterInstance = $this->objectManager->create(
            \Magento\Framework\App\ResourceConnection\ConnectionAdapterInterface::class,
            ['config' => $connectionConfig]
        );

        return $adapterInstance->getConnection();
    }
}

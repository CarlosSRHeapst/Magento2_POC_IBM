<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\App\ResourceConnection;

use Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory as ModelConnectionFactory;

/**
 * Connection adapter factory
 */
class ConnectionFactory extends ModelConnectionFactory
{
    /**
     * Create connection adapter instance
     *
     * @param array $connectionConfig
     * @return \Magento\Framework\DB\Adapter\AdapterInterfaceIbm
     * @throws \InvalidArgumentException
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
        //var_dump($connectionConfig);
        $connection = parent::create($connectionConfig);
        /** @var \Magento\Framework\DB\Adapter\DdlCache $ddlCache */
        $ddlCache = $this->objectManager->get(\Magento\Framework\DB\Adapter\DdlCache::class);
        $connection->setCacheAdapter($ddlCache);
        return $connection;
    }
}

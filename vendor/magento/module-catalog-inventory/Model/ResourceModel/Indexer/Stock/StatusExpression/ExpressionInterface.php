<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventory\Model\ResourceModel\Indexer\Stock\StatusExpression;

use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Zend_Db_Expr;

/**
 * Interface for composite status expressions for MySQL query.
 */
interface ExpressionInterface
{
    /**
     * Returns status expressions for MySQL query
     *
     * @param AdapterInterfaceIbm $connection
     * @param bool $isAggregate
     * @return Zend_Db_Expr
     */
    public function getExpression(AdapterInterfaceIbm $connection, bool $isAggregate): Zend_Db_Expr;
}

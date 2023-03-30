<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\App\ResourceConnection;

use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\DB\SelectFactory;

/**
 * Connection adapter interface
 *
 * @api
 */
interface ConnectionAdapterInterface
{
    /**
     * Get connection
     *
     * @param LoggerInterface|null $logger
     * @param SelectFactory|null $selectFactory
     * @return AdapterInterfaceIbm
     * @throws \InvalidArgumentException
     */
    public function getConnection(LoggerInterface $logger = null, SelectFactory $selectFactory = null);
}

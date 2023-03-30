<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup\Model;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;

/**
 * Factory for \Magento\Setup\Model\AdminAccount
 */
class AdminAccountFactory
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Create object
     *
     * @param AdapterInterfaceIbm $connection
     * @param array $data
     * @return AdminAccount
     */
    public function create(AdapterInterfaceIbm $connection, $data)
    {
        return new AdminAccount(
            $connection,
            $this->serviceLocator->get(\Magento\Framework\Encryption\Encryptor::class),
            $data
        );
    }
}

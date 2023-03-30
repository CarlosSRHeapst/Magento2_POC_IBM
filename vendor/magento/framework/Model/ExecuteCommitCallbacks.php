<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Model;

use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Psr\Log\LoggerInterface;

/**
 * Execute added callbacks for transaction commit.
 */
class ExecuteCommitCallbacks
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Execute callbacks after commit.
     *
     * @param AdapterInterfaceIbm $subject
     * @param AdapterInterfaceIbm $result
     * @return AdapterInterfaceIbm
     */
    public function afterCommit(AdapterInterfaceIbm $subject, AdapterInterfaceIbm $result): AdapterInterfaceIbm
    {
        if ($result->getTransactionLevel() === 0) {
            $callbacks = CallbackPool::get(spl_object_hash($subject));
            foreach ($callbacks as $callback) {
                try {
                    call_user_func($callback);
                } catch (\Throwable $e) {
                    $this->logger->critical($e);
                }
            }
        }

        return $result;
    }

    /**
     * Drop callbacks after rollBack.
     *
     * @param AdapterInterfaceIbm $subject
     * @param AdapterInterfaceIbm $result
     * @return AdapterInterfaceIbm
     */
    public function afterRollBack(AdapterInterfaceIbm $subject, AdapterInterfaceIbm $result): AdapterInterfaceIbm
    {
        CallbackPool::clear(spl_object_hash($subject));

        return $result;
    }
}

<?php
namespace Magento\Reports\Model\ResourceModel\Review\Collection;

/**
 * Interceptor class for @see \Magento\Reports\Model\ResourceModel\Review\Collection
 */
class Interceptor extends \Magento\Reports\Model\ResourceModel\Review\Collection implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Data\Collection\EntityFactory $entityFactory, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy, \Magento\Framework\Event\ManagerInterface $eventManager, \Magento\Review\Helper\Data $reviewData, \Magento\Review\Model\Rating\Option\VoteFactory $voteFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, ?\Magento\Framework\DB\Adapter\AdapterInterfaceIbm $connection = null, ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null)
    {
        $this->___init();
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $reviewData, $voteFactory, $storeManager, $connection, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurPage($displacement = 0)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCurPage');
        return $pluginInfo ? $this->___callPlugins('getCurPage', func_get_args(), $pluginInfo) : parent::getCurPage($displacement);
    }
}

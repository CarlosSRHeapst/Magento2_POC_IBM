<?php
namespace Magento\AdvancedSearch\Model\ResourceModel\Search\Grid\Collection;

/**
 * Interceptor class for @see \Magento\AdvancedSearch\Model\ResourceModel\Search\Grid\Collection
 */
class Interceptor extends \Magento\AdvancedSearch\Model\ResourceModel\Search\Grid\Collection implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Data\Collection\EntityFactory $entityFactory, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy, \Magento\Framework\Event\ManagerInterface $eventManager, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\DB\Helper $resourceHelper, \Magento\Framework\Registry $registry, ?\Magento\Framework\DB\Adapter\AdapterInterfaceIbm $connection = null, $resource = null)
    {
        $this->___init();
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $storeManager, $resourceHelper, $registry, $connection, $resource);
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
<?php
namespace Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection;

/**
 * Interceptor class for @see \Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection
 */
class Interceptor extends \Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Data\Collection\EntityFactory $entityFactory, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy, \Magento\Framework\Event\ManagerInterface $eventManager, \Magento\Sales\Model\ResourceModel\Report $resource, ?\Magento\Framework\DB\Adapter\AdapterInterfaceIbm $connection = null)
    {
        $this->___init();
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $resource, $connection);
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

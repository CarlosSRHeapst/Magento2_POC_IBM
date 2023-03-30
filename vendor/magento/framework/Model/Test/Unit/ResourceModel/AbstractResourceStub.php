<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Model\Test\Unit\ResourceModel;

use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Magento\Framework\Model\ResourceModel\AbstractResource;

class AbstractResourceStub extends AbstractResource
{
    /**
     * @var AdapterInterfaceIbm
     */
    private $connectionAdapter;

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        return null;
    }

    /**
     * Get connection
     *
     * @return AdapterInterfaceIbm
     */
    public function getConnection()
    {
        return $this->connectionAdapter;
    }

    /**
     * @param AdapterInterfaceIbm $adapter
     *
     * @return void
     */
    public function setConnection(AdapterInterfaceIbm $adapter)
    {
        $this->connectionAdapter = $adapter;
    }

    /**
     * @param DataObject $object
     * @param string $field
     * @param null $defaultValue
     * @param bool $unsetEmpty
     * @return $this
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function _serializeField(DataObject $object, $field, $defaultValue = null, $unsetEmpty = false)
    {
        return parent::_serializeField($object, $field, $defaultValue, $unsetEmpty);
    }

    /**
     * @param DataObject $object
     * @param string $field
     * @param null $defaultValue
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function _unserializeField(DataObject $object, $field, $defaultValue = null)
    {
        parent::_unserializeField($object, $field, $defaultValue);
    }
}

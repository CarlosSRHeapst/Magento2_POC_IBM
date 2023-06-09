<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\EntityManager\Test\Unit\Operation;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\Update;
use Magento\Framework\EntityManager\Operation\Update\UpdateMain;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

    /**
     * @var UpdateMain|MockObject
     */
    private $updateMain;

    /**
     * @var Update
     */
    private $update;

    protected function setUp(): void
    {
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateMain = $this->getMockBuilder(UpdateMain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->update = (new ObjectManager($this))->getObject(Update::class, [
            'metadataPool' => $this->metadataPool,
            'resourceConnection' => $this->resourceConnection,
            'updateMain' => $this->updateMain,
        ]);
    }

    public function testDuplicateExceptionProcessingOnExecute()
    {
        $this->expectException('Magento\Framework\Exception\AlreadyExistsException');
        $metadata = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $this->metadataPool->expects($this->any())->method('getMetadata')->willReturn($metadata);

        $connection = $this->getMockForAbstractClass(AdapterInterfaceIbm::class);
        $connection->expects($this->once())->method('rollback');
        $this->resourceConnection->expects($this->any())->method('getConnectionByName')->willReturn($connection);

        $this->updateMain->expects($this->once())->method('execute')->willThrowException(new DuplicateException());

        $entity = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->update->execute($entity);
    }
}

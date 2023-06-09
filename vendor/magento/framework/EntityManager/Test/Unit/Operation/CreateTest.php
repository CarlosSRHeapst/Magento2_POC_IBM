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
use Magento\Framework\EntityManager\Operation\Create;
use Magento\Framework\EntityManager\Operation\Create\CreateMain;
use Magento\Framework\EntityManager\Sequence\SequenceApplier;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
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
     * @var CreateMain|MockObject
     */
    private $createMain;

    /**
     * @var SequenceApplier|MockObject
     */
    private $sequenceApplier;

    /**
     * @var Create
     */
    private $create;

    protected function setUp(): void
    {
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createMain = $this->getMockBuilder(CreateMain::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sequenceApplier = $this->getMockBuilder(SequenceApplier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->create = $objectManagerHelper->getObject(Create::class, [
            'metadataPool' => $this->metadataPool,
            'resourceConnection' => $this->resourceConnection,
            'createMain' => $this->createMain,
            'sequenceApplier' => $this->sequenceApplier,
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

        $this->createMain->expects($this->once())->method('execute')->willThrowException(new DuplicateException());

        $entity = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->create->execute($entity);
    }
}

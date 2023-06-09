<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Indexer\Test\Unit;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\GridStructure;
use Magento\Framework\Indexer\ScopeResolver\FlatScopeResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridStructureTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    protected $resource;

    /**
     * @var FlatScopeResolver|MockObject
     */
    protected $flatScopeResolver;

    /**
     * @var AdapterInterfaceIbm|MockObject
     */
    protected $connection;

    /**
     * @var GridStructure
     */
    protected $object;

    protected function setUp(): void
    {
        $this->connection = $this->getMockBuilder(AdapterInterfaceIbm::class)
            ->getMock();
        $this->resource = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flatScopeResolver = $this->getMockBuilder(
            FlatScopeResolver::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->resource->expects($this->any())
            ->method('getConnection')
            ->with('write')
            ->willReturn($this->connection);
        $this->object = new GridStructure(
            $this->resource,
            $this->flatScopeResolver
        );
    }

    public function testDelete()
    {
        $index = 'index';
        $table = 'index_table';

        $this->flatScopeResolver->expects($this->once())
            ->method('resolve')
            ->with($index, [])
            ->willReturn($table);
        $this->connection->expects($this->once())
            ->method('isTableExists')
            ->with($table)
            ->willReturn(true);
        $this->connection->expects($this->once())
            ->method('dropTable')
            ->with($table);

        $this->object->delete($index);
    }

    public function testCreate()
    {
        $index = 'index';
        $fields = [
            [
                'type'     => 'searchable',
                'name'     => 'field',
                'dataType' => 'int'
            ]
        ];
        $tableName = 'index_table';
        $idxName = 'idxName';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flatScopeResolver->expects($this->once())
            ->method('resolve')
            ->with($index, [])
            ->willReturn($tableName);
        $this->connection->expects($this->once())
            ->method('newTable')
            ->with($tableName)
            ->willReturn($table);
        $table->expects($this->any())
            ->method('addColumn')
            ->willReturnMap(
                [
                    ['entity_id', Table::TYPE_INTEGER, 10, ['unsigned' => true, 'nullable' => false], 'Entity ID'],
                    ['field', Table::TYPE_INTEGER, null]
                ]
            );
        $this->connection->expects($this->once())
            ->method('createTable')
            ->with($table);
        $this->resource->expects($this->once())
            ->method('getIdxName')
            ->with($tableName, ['field'], AdapterInterfaceIbm::INDEX_TYPE_FULLTEXT)
            ->willReturn($idxName);
        $table->expects($this->once())
            ->method('addIndex')
            ->with($idxName, ['field'], ['type' => AdapterInterfaceIbm::INDEX_TYPE_FULLTEXT]);
        $this->object->create($index, $fields);
    }
}

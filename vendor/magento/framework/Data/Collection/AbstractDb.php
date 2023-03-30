<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Data\Collection;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\DB\Adapter\AdapterInterfaceIbm;
use Magento\Framework\DB\Select;
use Magento\Framework\Api\ExtensionAttribute\JoinDataInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Base items collection class
 *
 * phpcs:disable Magento2.Classes.AbstractApi
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
abstract class AbstractDb extends \Magento\Framework\Data\Collection
{
    /**
     * DB connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterfaceIbm
     */
    protected $_conn;

    /**
     * Select object
     *
     * @var \Magento\Framework\DB\Select
     */
    protected $_select;

    /**
     * Identifier field name for collection items
     *
     * Can be used by collections with items without defined
     *
     * @var string
     */
    protected $_idFieldName;

    /**
     * List of bound variables for select
     *
     * @var array
     */
    protected $_bindParams = [];

    /**
     * All collection data array
     * Used for getData method
     *
     * @var array
     */
    protected $_data = null;

    /**
     * Fields map for correlation names & real selected fields
     *
     * @var array
     */
    protected $_map = null;

    /**
     * Database's statement for fetch item one by one
     *
     * @var \Zend_Db_Statement_Pdo
     */
    protected $_fetchStmt = null;

    /**
     * Whether orders are rendered
     *
     * @var bool
     */
    protected $_isOrdersRendered = false;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var FetchStrategyInterface
     */
    private $_fetchStrategy;

    /**
     * Join processor is set only if extension attributes were joined before the collection was loaded.
     *
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface|null
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param Logger $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\DB\Adapter\AdapterInterfaceIbm $connection
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        Logger $logger,
        FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\DB\Adapter\AdapterInterfaceIbm $connection = null
    ) {
        parent::__construct($entityFactory);
        $this->_fetchStrategy = $fetchStrategy;
        if ($connection !== null) {
            $this->setConnection($connection);
        }
        $this->_logger = $logger;
    }

    /**
     * Get resource instance.
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    abstract public function getResource();

    /**
     * Add variable to bind list
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addBindParam($name, $value)
    {
        $this->_bindParams[$name] = $value;
        return $this;
    }

    /**
     * Specify collection objects id field name
     *
     * @param string $fieldName
     * @return $this
     */
    protected function _setIdFieldName($fieldName)
    {
        $this->_idFieldName = $fieldName;
        return $this;
    }

    /**
     * Id field name getter
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return $this->_idFieldName;
    }

    /**
     * Get collection item identifier
     *
     * @param \Magento\Framework\DataObject $item
     * @return mixed
     */
    protected function _getItemId(\Magento\Framework\DataObject $item)
    {
        if ($field = $this->getIdFieldName()) {
            return $item->getData($field);
        }
        return parent::_getItemId($item);
    }

    /**
     * Set database connection adapter
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterfaceIbm $conn
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setConnection(\Magento\Framework\DB\Adapter\AdapterInterfaceIbm $conn)
    {
        $this->_conn = $conn;
        $this->_select = $this->_conn->select();
        //$select = $this->_select;
        //DB2MOD 29/03/2023 Investigación
        //$selectString = (string) $select;
        //var_dump($selectString);
        //RESULTA STRING VACIO
        ////////////////////////////////////////////////////////////
        /*$selectString = $this->_select;
        $needle   = 'theme_path';

        if (str_contains($selectString, $needle)) {
            var_dump($selectString);
            sab;
        } else {

        }*/
        ///////////////////////////////////////////////////////////
        $this->_isOrdersRendered = false;
        return $this;
    }

    /**
     * Get \Magento\Framework\DB\Select instance
     *
     * @return Select
     */
    public function getSelect()
    {
        //var_dump(gettype($this->_select)); //Object
        //$selectString = (string) $this->_select;
        //var_dump($selectString); //29/03/2023
        //2 Stacktraces diferentes a analizar (Creacion query maintable y seleccion theme_path)

        //var_dump($selectString); //Procesamiento interno de SELECT
        /* 
        string(0) ""
        string(0) ""
        string(50) "SELECT `main_table`.* FROM `theme` AS `main_table`"
        string(0) ""
        string(152) "SELECT `main_table`.* FROM `theme` AS `main_table` WHERE ("area" = 'frontend') AND ("theme_path" = 'Magento/blank') AND ("theme_path" = 'Magento/blank')"
        */
        
        /*$needle   = 'theme_path';

        if (str_contains($selectString, $needle)) {
            var_dump($selectString);
            sa;
        } else {

        }*/
        return $this->_select;
    }

    /**
     * Retrieve connection object
     *
     * @return AdapterInterfaceIbm
     */
    public function getConnection()
    {
        return $this->_conn;
    }

    /**
     * Get collection size
     *
     * @return int
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $sql = $this->getSelectCountSql();
            $this->_totalRecords = $this->_totalRecords ?? $this->getConnection()->fetchOne($sql, $this->_bindParams);
        }
        return (int)$this->_totalRecords;
    }

    /**
     * Get SQL for get record count
     *
     * @return Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();
        
        $countSelect = clone $this->getSelect();
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        $part = $this->getSelect()->getPart(\Magento\Framework\DB\Select::GROUP);
        if (!is_array($part) || !count($part)) {
            $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
            return $countSelect;
        }

        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
        $group = $this->getSelect()->getPart(\Magento\Framework\DB\Select::GROUP);
        $countSelect->columns(new \Zend_Db_Expr(("COUNT(DISTINCT ".implode(", ", $group).")")));
        return $countSelect;
    }

    /**
     * Get sql select string or object
     *
     * @param   bool $stringMode
     * @return  string|\Magento\Framework\DB\Select
     */
    public function getSelectSql($stringMode = false)
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 7 " . PHP_EOL;
        if ($stringMode) {
            return $this->_select->__toString();
        }
        return $this->_select;
    }

    /**
     * Add select order
     *
     * @param   string $field
     * @param   string $direction
     * @return  $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        return $this->_setOrder($field, $direction);
    }

    /**
     * Sets order and direction.
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        return $this->_setOrder($field, $direction);
    }

    /**
     * Add select order to the beginning
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function unshiftOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        return $this->_setOrder($field, $direction, true);
    }

    /**
     * Add ORDER BY to the end or to the beginning
     *
     * @param string $field
     * @param string $direction
     * @param bool $unshift
     * @return $this
     */
    private function _setOrder($field, $direction, $unshift = false)
    {
        $this->_isOrdersRendered = false;
        $field = (string)$this->_getMappedField($field);
        $direction = strtoupper($direction) == self::SORT_ORDER_ASC ? self::SORT_ORDER_ASC : self::SORT_ORDER_DESC;

        unset($this->_orders[$field]);
        // avoid ordering by the same field twice
        if ($unshift) {
            $orders = [$field => $direction];
            foreach ($this->_orders as $key => $dir) {
                $orders[$key] = $dir;
            }
            $this->_orders = $orders;
        } else {
            $this->_orders[$field] = $direction;
        }
        return $this;
    }

    /**
     * Render sql select conditions
     *
     * @return  $this
     */
    protected function _renderFilters()
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 6 " . PHP_EOL;
        if ($this->_isFiltersRendered) {
            return $this;
        }

        $this->_renderFiltersBefore();
        //DB2MOD aqui asigna campos al objeto select (no hace nada)
        foreach ($this->_filters as $filter) {
            switch ($filter['type']) {
                case 'or':
                    $condition = $this->_conn->quoteInto($filter['field'] . '=?', $filter['value']);
                    $this->_select->orWhere($condition);
                    break;
                case 'string':
                    $this->_select->where($filter['value']);
                    break;
                case 'public':
                    $field = $this->_getMappedField($filter['field']);
                    $condition = $filter['value'];
                    $this->_select->where($this->_getConditionSql($field, $condition), null, Select::TYPE_CONDITION);
                    break;
                default:
                    $condition = $this->_conn->quoteInto($filter['field'] . '=?', $filter['value']);
                    $this->_select->where($condition);
            }
        }
        $this->_isFiltersRendered = true;
        return $this;
    }

    /**
     * Hook for operations before rendering filters
     *
     * @return void
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    protected function _renderFiltersBefore()
    {
    }
    // phpcs:enable

    /**
     * Add field filter to collection
     *
     * @see self::_getConditionSql for $condition
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 5 " . PHP_EOL;
        //var_dump($field); //DB2MOD 29/03/2023 ENCONTRE LA PASADA DE main_table y theme_path
        if (is_array($field)) {
            $conditions = [];
            foreach ($field as $key => $value) {
                $conditions[] = $this->_translateCondition($value, isset($condition[$key]) ? $condition[$key] : null);
            }

            $resultCondition = '(' . implode(') ' . \Magento\Framework\DB\Select::SQL_OR . ' (', $conditions) . ')';
        } else {
            $resultCondition = $this->_translateCondition($field, $condition);
        }
        //echo " Print Result Condition " . PHP_EOL;
        //var_dump($resultCondition); //DB2 MOD Aqui esta el problema
        $this->_select->where($resultCondition, null, Select::TYPE_CONDITION);

        return $this;
    }

    /**
     * Build sql where condition part
     *
     * @param   string|array $field
     * @param   null|string|array $condition
     * @return  string
     */
    protected function _translateCondition($field, $condition)
    {
        //DB2MOD No se detecto este $this->getConnection()->quoteIdentifier, sin embargo afecta
        $field = $this->_getMappedField($field);
        return $this->_getConditionSql($field, $condition);
    }

    /**
     * Try to get mapped field name for filter to collection
     *
     * @param   string $field
     * @return  string
     */
    protected function _getMappedField($field)
    {
        $mapper = $this->_getMapper();

        if (isset($mapper['fields'][$field])) {
            $mappedField = $mapper['fields'][$field];
        } else {
            $mappedField = $field;
        }

        return $mappedField;
    }

    /**
     * Retrieve mapper data
     *
     * @return array|bool|null
     */
    protected function _getMapper()
    {
        if (isset($this->_map)) {
            return $this->_map;
        } else {
            return false;
        }
    }

    /**
     * Build SQL statement for condition
     *
     * If $condition integer or string - exact value will be filtered ('eq' condition)
     *
     * If $condition is array - one of the following structures is expected:
     * - array("from" => $fromValue, "to" => $toValue)
     * - array("eq" => $equalValue)
     * - array("neq" => $notEqualValue)
     * - array("like" => $likeValue)
     * - array("in" => array($inValues))
     * - array("nin" => array($notInValues))
     * - array("notnull" => $valueIsNotNull)
     * - array("null" => $valueIsNull)
     * - array("moreq" => $moreOrEqualValue)
     * - array("gt" => $greaterValue)
     * - array("lt" => $lessValue)
     * - array("gteq" => $greaterOrEqualValue)
     * - array("lteq" => $lessOrEqualValue)
     * - array("finset" => $valueInSet)
     * - array("regexp" => $regularExpression)
     * - array("seq" => $stringValue)
     * - array("sneq" => $stringValue)
     *
     * If non matched - sequential array is expected and OR conditions
     * will be built using above mentioned structure
     *
     * @param string $fieldName
     * @param integer|string|array $condition
     * @return string
     */
    protected function _getConditionSql($fieldName, $condition)
    {
        return $this->getConnection()->prepareSqlCondition($fieldName, $condition);
    }

    /**
     * Return the field name for the condition.
     *
     * @param string $fieldName
     * @return string
     */
    protected function _getConditionFieldName($fieldName)
    {
        return $fieldName;
    }

    /**
     * Render sql select orders
     *
     * @return  $this
     */
    protected function _renderOrders()
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 4 " . PHP_EOL;
        if (!$this->_isOrdersRendered) {
            foreach ($this->_orders as $field => $direction) {
                $this->_select->order(new \Zend_Db_Expr($field . ' ' . $direction));
            }
            $this->_isOrdersRendered = true;
        }

        return $this;
    }

    /**
     * Render sql select limit
     *
     * @return  $this
     */
    protected function _renderLimit()
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 3 " . PHP_EOL;
        if ($this->_pageSize) {
            $this->_select->limitPage($this->getCurPage(), $this->_pageSize);
        }

        return $this;
    }

    /**
     * Set select distinct
     *
     * @param   bool $flag
     * @return  $this
     */
    public function distinct($flag)
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 2 " . PHP_EOL;
        $this->_select->distinct($flag);
        return $this;
    }

    /**
     * Before load action
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        return $this;
    }

    /**
     * Load data
     *
     * @param   bool $printQuery
     * @param   bool $logQuery
     * @return  $this
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }
        //DB2MOD 29/03/2023
        return $this->loadWithFilter($printQuery, $logQuery);
    }

    /**
     * Load data with filter in place
     *
     * @param   bool $printQuery
     * @param   bool $logQuery
     * @return  $this
     */
    public function loadWithFilter($printQuery = false, $logQuery = false)
    {
        $this->_beforeLoad();
        $this->_renderFilters()->_renderOrders()->_renderLimit();
        $this->printLogQuery($printQuery, $logQuery);
        //DB2MOD Getdata (Otra ves 29/03/2023)
        $data = $this->getData();
        $this->resetData();
        if (is_array($data)) {
            foreach ($data as $row) {

                $item = $this->getNewEmptyItem();

                if ($this->getIdFieldName()) { 
                    $item->setIdFieldName($this->getIdFieldName());
                }
                
////////////////////////////////////////////////////////////////
                $item->addData($row);
                //PROTECTED
                //var_dump($item->_data);
                $this->beforeAddLoadedItem($item);
                $this->addItem($item);

                
            }
        }
        $this->_setIsLoaded();
        $this->_afterLoad();

        return $this;
    }

    /**
     * Let do something before add loaded item in collection
     *
     * @param \Magento\Framework\DataObject $item
     * @return \Magento\Framework\DataObject
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        return $item;
    }

    /**
     * Returns an items collection.
     * Returns a collection item that corresponds to the fetched row
     * and moves the internal data pointer ahead
     *
     * @return  \Magento\Framework\Model\AbstractModel|bool
     */
    public function fetchItem()
    {
        if (null === $this->_fetchStmt) {
            $this->_renderOrders()->_renderLimit();

            $this->_fetchStmt = $this->getConnection()->query($this->getSelect());
        }
        $data = $this->_fetchStmt->fetch();
        if (!empty($data) && is_array($data)) {
            $item = $this->getNewEmptyItem();
            if ($this->getIdFieldName()) {
                $item->setIdFieldName($this->getIdFieldName());
            }
            $item->setData($data);

            return $item;
        }
        return false;
    }

    /**
     * Overridden to use _idFieldName by default.
     *
     * @param string|null $valueField
     * @param string $labelField
     * @param array $additional
     * @return array
     */
    protected function _toOptionArray($valueField = null, $labelField = 'name', $additional = [])
    {
        if ($valueField === null) {
            $valueField = $this->getIdFieldName();
        }
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

    /**
     * Overridden to use _idFieldName by default.
     *
     * @param   string $valueField
     * @param   string $labelField
     * @return  array
     */
    protected function _toOptionHash($valueField = null, $labelField = 'name')
    {
        if ($valueField === null) {
            $valueField = $this->getIdFieldName();
        }
        return parent::_toOptionHash($valueField, $labelField);
    }

    /**
     * Get all data array for collection
     *
     * @return array
     */
    public function getData()
    {
        //DB2MOD Investigacion de construccion
        if ($this->_data === null) {
            $this->_renderFilters()->_renderOrders()->_renderLimit();
            $select = $this->getSelect(); 
            //$selectString = (string) $select;
            //var_dump($selectString);
            //DB2MOD 29/03/2023 investigación
            //var_dump(gettype($select));   //objeto select creado en inicio
            $this->_data = $this->_fetchAll($select);
            $this->_afterLoadData();
        }
        return $this->_data;
    }

    /**
     * Process loaded collection data
     *
     * @return $this
     */
    protected function _afterLoadData()
    {
        return $this;
    }

    /**
     * Reset loaded for collection data array
     *
     * @return $this
     */
    public function resetData()
    {
        $this->_data = null;
        return $this;
    }

    /**
     * Process loaded collection
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        return $this;
    }

    /**
     * Load the data.
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        return $this->load($printQuery, $logQuery);
    }

    /**
     * Print and/or log query
     *
     * @param   bool $printQuery
     * @param   bool $logQuery
     * @param   string $sql
     * @return  $this
     */
    public function printLogQuery($printQuery = false, $logQuery = false, $sql = null)
    {
        if ($printQuery || $this->getFlag('print_query')) {
            //phpcs:ignore Magento2.Security.LanguageConstruct
            echo $sql === null ? $this->getSelect()->__toString() : $sql;
        }

        if ($logQuery || $this->getFlag('log_query')) {
            $this->_logQuery($sql);
        }
        return $this;
    }

    /**
     * Log query
     *
     * @param string $sql
     * @return void
     */
    protected function _logQuery($sql)
    {
        $this->_logger->info($sql === null ? $this->getSelect()->__toString() : $sql);
    }

    /**
     * Reset collection
     *
     * @return $this
     */
    protected function _reset()
    {
        $this->getSelect()->reset();
        $this->_initSelect();
        $this->_setIsLoaded(false);
        $this->_items = [];
        $this->_data = null;
        $this->extensionAttributesJoinProcessor = null;
        return $this;
    }

    /**
     * Fetch collection data
     *
     * @param Select $select
     * @return array
     */
    protected function _fetchAll(Select $select)
    {
        $data = $this->_fetchStrategy->fetchAll($select, $this->_bindParams);
        if ($this->extensionAttributesJoinProcessor) {
            foreach ($data as $key => $dataItem) {
                $data[$key] = $this->extensionAttributesJoinProcessor->extractExtensionAttributes(
                    $this->_itemObjectClass,
                    $dataItem
                );
            }
        }
        return $data;
    }

    /**
     * Add filter to Map
     *
     * @param string $filter
     * @param string $alias
     * @param string $group default 'fields'
     * @return $this
     */
    public function addFilterToMap($filter, $alias, $group = 'fields')
    {
        if ($this->_map === null) {
            $this->_map = [$group => []];
        } elseif (empty($this->_map[$group])) {
            $this->_map[$group] = [];
        }
        $this->_map[$group][$filter] = $alias;

        return $this;
    }

    /**
     * Clone $this->_select during cloning collection, otherwise both collections will share the same $this->_select
     *
     * @return void
     */
    public function __clone()
    {
        //echo "DB2MOD Clase Abstract DB pase por aqui 1 " . PHP_EOL;
        if (is_object($this->_select)) {
            $this->_select = clone $this->_select;
        }
    }

    /**
     * Init select
     *
     * @return void
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    protected function _initSelect() //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
        // no implementation, should be overridden in children classes
    }
    // phpcs:enable

    /**
     * Join extension attribute.
     *
     * @param JoinDataInterface $join
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @return $this
     */
    public function joinExtensionAttribute(
        JoinDataInterface $join,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $selectFrom = $this->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);
        $joinRequired = !isset($selectFrom[$join->getReferenceTableAlias()]);
        if ($joinRequired) {
            $joinOn = $this->getMainTableAlias() . '.' . $join->getJoinField()
                . ' = ' . $join->getReferenceTableAlias() . '.' . $join->getReferenceField();
            $this->getSelect()->joinLeft(
                [$join->getReferenceTableAlias() => $this->getResource()->getTable($join->getReferenceTable())],
                $joinOn,
                []
            );
        }
        $columns = [];
        foreach ($join->getSelectFields() as $selectField) {
            $fieldWIthDbPrefix = $selectField[JoinDataInterface::SELECT_FIELD_WITH_DB_PREFIX];
            $columns[$selectField[JoinDataInterface::SELECT_FIELD_INTERNAL_ALIAS]] = $fieldWIthDbPrefix;
            $this->addFilterToMap($selectField[JoinDataInterface::SELECT_FIELD_EXTERNAL_ALIAS], $fieldWIthDbPrefix);
        }
        $this->getSelect()->columns($columns);
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        return $this;
    }

    /**
     * Get collection item object class name.
     *
     * @return string
     */
    public function getItemObjectClass()
    {
        return $this->_itemObjectClass;
    }

    /**
     * Identify main table alias or its name if alias is not defined.
     *
     * @return string
     * @throws \LogicException
     */
    private function getMainTableAlias()
    {
        foreach ($this->getSelect()->getPart(\Magento\Framework\DB\Select::FROM) as $tableAlias => $tableMetadata) {
            if ($tableMetadata['joinType'] == 'from') {
                return $tableAlias;
            }
        }
        throw new \LogicException("Main table cannot be identified.");
    }

    /**
     * @inheritdoc
     * @since 100.0.11
     */
    public function __sleep()
    {
        return array_diff(
            parent::__sleep(),
            ['_fetchStrategy', '_logger', '_conn', 'extensionAttributesJoinProcessor']
        );
    }

    /**
     * @inheritdoc
     * @since 100.0.11
     */
    public function __wakeup()
    {
        parent::__wakeup();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_logger = $objectManager->get(Logger::class);
        $this->_fetchStrategy = $objectManager->get(FetchStrategyInterface::class);
        $this->_conn = $objectManager->get(ResourceConnection::class)->getConnection();
    }
}

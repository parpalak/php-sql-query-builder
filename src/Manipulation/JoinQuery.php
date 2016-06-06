<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 12/25/14
 * Time: 11:41 AM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Sql\QueryBuilder\Manipulation;

use NilPortugues\Sql\QueryBuilder\Syntax\SyntaxFactory;
use NilPortugues\Sql\QueryBuilder\Syntax\Where;

/**
 * Class JoinQuery.
 */
class JoinQuery
{
    const JOIN_LEFT = 'LEFT';
    const JOIN_RIGHT = 'RIGHT';
    const JOIN_INNER = 'INNER';
    const JOIN_CROSS = 'CROSS';

    /**
     * @var Where
     */
    protected $joinCondition;

    /**
     * @var bool
     */
    protected $isJoin = false;

    /**
     * @var string
     */
    protected $joinType;

    /**
     * @var array
     */
    protected $joins = [];

    /**
     * @var Select
     */
    protected $select;

    /**
     * @param Select $select
     */
    public function __construct(Select $select)
    {
        $this->select = $select;
    }

    /**
     * @param string $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->select->setTable($table);

        return $this;
    }

    /**
     * @param string   $table
     * @param string   $selfColumn
     * @param string   $refColumn
     * @param string[] $columns
     *
     * @return Select
     */
    public function leftJoin($table, $selfColumn = null, $refColumn = null, $columns = [])
    {
        return $this->join($table, $selfColumn, $refColumn, $columns, self::JOIN_LEFT);
    }

    /**
     * @param string|array $table
     * @param string       $selfColumn
     * @param string       $refColumn
     * @param string[]     $columns
     * @param string       $joinType
     *
     * @return Select
     */
    public function join(
        $table,
        $selfColumn = null,
        $refColumn = null,
        $columns = [],
        $joinType = null
    ) {
        if (\is_array($table) && !empty($table)) {
            $key = \key($table);

            $tableName = is_numeric($key) ? \current($table) : $key;
        }
        else {
            $tableName = $table;
        }

        if (!isset($this->joins[$tableName])) {
            $select = QueryFactory::createSelect($table);
            $select->setColumns($columns);
            $select->setJoinType($joinType);
            $select->setParentQuery($this->select);
            $this->addJoin($select, $selfColumn, $refColumn);
        }

        return $this->joins[$tableName];
    }

    /**
     * @param Select $select
     * @param string $selfColumn
     * @param string $refColumn
     *
     * @return Select
     */
    public function addJoin(Select $select, $selfColumn, $refColumn)
    {
        $select->isJoin(true);
        $queryTableName = $select->getTable()->getAliasOrName();

        if (!isset($this->joins[$queryTableName])) {
            $newColumn = array($selfColumn);
            $select->joinCondition()->equals(
                $refColumn,
                SyntaxFactory::createColumn($newColumn, $this->select->getTable())
            );
            $this->joins[$queryTableName] = $select;
        }

        return $this->joins[$queryTableName];
    }

    /**
     * Transforms Select in a joint.
     *
     * @param bool $isJoin
     *
     * @return $this
     */
    public function setJoin($isJoin = true)
    {
        $this->isJoin = $isJoin;

        return $this;
    }

    /**
     * @param string   $table
     * @param string   $selfColumn
     * @param string   $refColumn
     * @param string[] $columns
     *
     * @internal param null $selectClass
     *
     * @return Select
     */
    public function rightJoin($table, $selfColumn = null, $refColumn = null, $columns = [])
    {
        return $this->join($table, $selfColumn, $refColumn, $columns, self::JOIN_RIGHT);
    }

    /**
     * @param string   $table
     * @param string   $selfColumn
     * @param string   $refColumn
     * @param string[] $columns
     *
     * @return Select
     */
    public function crossJoin($table, $selfColumn = null, $refColumn = null, $columns = [])
    {
        return $this->join($table, $selfColumn, $refColumn, $columns, self::JOIN_CROSS);
    }

    /**
     * @param string   $table
     * @param string   $selfColumn
     * @param string   $refColumn
     * @param string[] $columns
     *
     * @return Select
     */
    public function innerJoin($table, $selfColumn = null, $refColumn = null, $columns = [])
    {
        return $this->join($table, $selfColumn, $refColumn, $columns, self::JOIN_INNER);
    }

    /**
     * Alias to joinCondition.
     *
     * @return Where
     */
    public function on()
    {
        return $this->joinCondition();
    }

    /**
     * WHERE constrains used for the ON clause of a (LEFT/RIGHT/INNER/CROSS) JOIN.
     *
     * @return Where
     */
    public function joinCondition()
    {
        if (!isset($this->joinCondition)) {
            $this->joinCondition = QueryFactory::createWhere($this->select);
        }

        return $this->joinCondition;
    }

    /**
     * @return bool
     */
    public function isJoinSelect()
    {
        return $this->isJoin;
    }

    /**
     * @return bool
     */
    public function isJoin()
    {
        return $this->isJoin;
    }

    /**
     * @return \NilPortugues\Sql\QueryBuilder\Syntax\Where
     */
    public function getJoinCondition()
    {
        return $this->joinCondition;
    }

    /**
     * @param \NilPortugues\Sql\QueryBuilder\Syntax\Where $joinCondition
     *
     * @return $this
     */
    public function setJoinCondition($joinCondition)
    {
        $this->joinCondition = $joinCondition;

        return $this;
    }

    /**
     * @return string
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * @param string $joinType
     *
     * @return $this
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;

        return $this;
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param array $joins
     *
     * @return $this
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;

        return $this;
    }

    /**
     * @return array
     */
    public function getAllJoins()
    {
        $joins = $this->joins;

        foreach ($this->joins as $join) {
            $joins = \array_merge($joins, $join->getAllJoins());
        }

        return $joins;
    }
}

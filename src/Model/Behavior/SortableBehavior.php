<?php
namespace DejwCake\Helpers\Model\Behavior;

use Cake\Collection\Collection;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;

/**
 * Makes the table to which this is attached to behave like a nested set and
 * provides methods for managing and retrieving information out of the derived
 * hierarchical structure.
 *
 * Tables attaching this behavior are required to have a column referencing the
 * parent row, and two other numeric columns (lft and rght) where the implicit
 * order will be cached.
 *
 * For more information on what is a nested set and a how it works refer to
 * http://www.sitepoint.com/hierarchical-data-database-2/
 */
class SortableBehavior extends Behavior
{

//    TODO change from tree to sort
//    TODO add scope
    /**
     * Cached copy of the first column in a table's primary key.
     *
     * @var string
     */
    protected $_primaryKey;

    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'implementedFinders' => [
        ],
        'implementedMethods' => [
//            'moveUp' => 'moveUp',
//            'moveDown' => 'moveDown',
            'recover' => 'recover',
            'setNewSort' => 'setNewSort',
        ],
        'sortField' => 'sort',
        'foreignKey' => null,
        'scope' => null,
        'recoverSort' => null,
        'recoverUseSortField' => false,
    ];

    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
    }

    /**
     * Before save listener.
     * Transparently manages setting the lft and rght fields if the parent field is
     * included in the parameters to be saved.
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity the entity that is going to be saved
     * @return void
     * @throws \RuntimeException if the parent to set for the node is invalid
     */
    public function beforeSave(Event $event, EntityInterface $entity)
    {
        $isNew = $entity->isNew();
        $config = $this->getConfig();

        $max = $this->_getMax($this->_getForeignKeyValue($entity));
        if ($isNew) {
            $entity->set($config['sortField'], $max + 1);
            return;
        } else {
            if($entity->get($config['sortField']) > $max + 1) {
                $entity->set($config['sortField'], $max + 1);
            }
        }
    }

    /**
     * After save listener.
     *
     * @param \Cake\Event\Event $event The beforeSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity the entity that is going to be saved
     * @return void
     * @throws \RuntimeException if the parent to set for the node is invalid
     */
    public function afterSave(Event $event, EntityInterface $entity)
    {
        $isNew = $entity->isNew();

        if (!$isNew) {
            $config = $this->getConfig();
            $primaryKey = $this->_getPrimaryKey();

            $item = $this->_scope($this->_table->query(), $this->_getForeignKeyValue($entity))
                ->where([$config['sortField'] => $entity->get($config['sortField'])])
                ->where([$this->_table->aliasField($primaryKey).' !=' => $entity->get($primaryKey)])
                ->first();

            if(!empty($item) || ($config['foreignKey'] && $entity->dirty($config['foreignKey']))) {
                $this->recover();
            }
            return;
        }
    }

    /**
     * Also deletes the nodes in the subtree of the entity to be delete
     *
     * @param \Cake\Event\Event $event The beforeDelete event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity that is going to be saved
     * @return void
     */
    public function afterDelete(Event $event, EntityInterface $entity)
    {
        $config = $this->getConfig();
        $this->_ensureFields($entity);
        $sort = $entity->get($config['sortField']);
        $foreignKeyValue = $this->_getForeignKeyValue($entity);

        $expression = new QueryExpression($config['sortField'].' = '.$config['sortField'].' - 1');
        $this->_scope($this->_table->query(), $foreignKeyValue)
            ->update()
            ->set([$expression])
            ->where([$config['sortField'].' >' => $sort])
            ->execute();
    }

    /**
     * Reorders the node without changing its parent.
     *
     * If the node is the first child, or is a top level node with no previous node
     * this method will return false
     *
     * @param \Cake\Datasource\EntityInterface $node The node to move
     * @param int|bool $number How many places to move the node, or true to move to first position
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When node was not found
     * @return \Cake\ORM\Entity|bool $node The node after being moved or false on failure
     */
//    public function moveUp(EntityInterface $node, $number = 1)
//    {
//        if ($number < 1) {
//            return false;
//        }
//
//        return $this->_table->getConnection()->transactional(function () use ($node, $number) {
//            $this->_ensureFields($node);
//
//            return $this->_moveUp($node, $number);
//        });
//    }

    /**
     * Helper function used with the actual code for moveUp
     *
     * @param \Cake\Datasource\EntityInterface $node The node to move
     * @param int|bool $number How many places to move the node, or true to move to first position
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When node was not found
     * @return \Cake\ORM\Entity|bool $node The node after being moved or false on failure
     */
//    protected function _moveUp($node, $number)
//    {
//        $config = $this->getConfig();
//        list($parent, $left, $right) = [$config['parent'], $config['left'], $config['right']];
//        list($nodeParent, $nodeLeft, $nodeRight) = array_values($node->extract([$parent, $left, $right]));
//
//        $targetNode = null;
//        if ($number !== true) {
//            $targetNode = $this->_scope($this->_table->find())
//                ->select([$left, $right])
//                ->where(["$parent IS" => $nodeParent])
//                ->where(function ($exp) use ($config, $nodeLeft) {
//                    return $exp->lt($config['rightField'], $nodeLeft);
//                })
//                ->orderDesc($config['leftField'])
//                ->offset($number - 1)
//                ->limit(1)
//                ->first();
//        }
//        if (!$targetNode) {
//            $targetNode = $this->_scope($this->_table->find())
//                ->select([$left, $right])
//                ->where(["$parent IS" => $nodeParent])
//                ->where(function ($exp) use ($config, $nodeLeft) {
//                    return $exp->lt($config['rightField'], $nodeLeft);
//                })
//                ->orderAsc($config['leftField'])
//                ->limit(1)
//                ->first();
//
//            if (!$targetNode) {
//                return $node;
//            }
//        }
//
//        list($targetLeft) = array_values($targetNode->extract([$left, $right]));
//        $edge = $this->_getMax();
//        $leftBoundary = $targetLeft;
//        $rightBoundary = $nodeLeft - 1;
//
//        $nodeToEdge = $edge - $nodeLeft + 1;
//        $shift = $nodeRight - $nodeLeft + 1;
//        $nodeToHole = $edge - $leftBoundary + 1;
//        $this->_sync($nodeToEdge, '+', "BETWEEN {$nodeLeft} AND {$nodeRight}");
//        $this->_sync($shift, '+', "BETWEEN {$leftBoundary} AND {$rightBoundary}");
//        $this->_sync($nodeToHole, '-', "> {$edge}");
//
//        $node->set($left, $targetLeft);
//        $node->set($right, $targetLeft + ($nodeRight - $nodeLeft));
//
//        $node->dirty($left, false);
//        $node->dirty($right, false);
//
//        return $node;
//    }

    /**
     * Reorders the node without changing the parent.
     *
     * If the node is the last child, or is a top level node with no subsequent node
     * this method will return false
     *
     * @param \Cake\Datasource\EntityInterface $node The node to move
     * @param int|bool $number How many places to move the node or true to move to last position
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When node was not found
     * @return \Cake\ORM\Entity|bool the entity after being moved or false on failure
     */
//    public function moveDown(EntityInterface $node, $number = 1)
//    {
//        if ($number < 1) {
//            return false;
//        }
//
//        return $this->_table->getConnection()->transactional(function () use ($node, $number) {
//            $this->_ensureFields($node);
//
//            return $this->_moveDown($node, $number);
//        });
//    }

    /**
     * Helper function used with the actual code for moveDown
     *
     * @param \Cake\Datasource\EntityInterface $node The node to move
     * @param int|bool $number How many places to move the node, or true to move to last position
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When node was not found
     * @return \Cake\ORM\Entity|bool $node The node after being moved or false on failure
     */
//    protected function _moveDown($node, $number)
//    {
//        $config = $this->getConfig();
//        list($parent, $left, $right) = [$config['parent'], $config['left'], $config['right']];
//        list($nodeParent, $nodeLeft, $nodeRight) = array_values($node->extract([$parent, $left, $right]));
//
//        $targetNode = null;
//        if ($number !== true) {
//            $targetNode = $this->_scope($this->_table->find())
//                ->select([$left, $right])
//                ->where(["$parent IS" => $nodeParent])
//                ->where(function ($exp) use ($config, $nodeRight) {
//                    /* @var \Cake\Database\Expression\QueryExpression $exp */
//                    return $exp->gt($config['leftField'], $nodeRight);
//                })
//                ->orderAsc($config['leftField'])
//                ->offset($number - 1)
//                ->limit(1)
//                ->first();
//        }
//        if (!$targetNode) {
//            $targetNode = $this->_scope($this->_table->find())
//                ->select([$left, $right])
//                ->where(["$parent IS" => $nodeParent])
//                ->where(function ($exp) use ($config, $nodeRight) {
//                    /* @var \Cake\Database\Expression\QueryExpression $exp */
//                    return $exp->gt($config['leftField'], $nodeRight);
//                })
//                ->orderDesc($config['leftField'])
//                ->limit(1)
//                ->first();
//
//            if (!$targetNode) {
//                return $node;
//            }
//        }
//
//        list(, $targetRight) = array_values($targetNode->extract([$left, $right]));
//        $edge = $this->_getMax();
//        $leftBoundary = $nodeRight + 1;
//        $rightBoundary = $targetRight;
//
//        $nodeToEdge = $edge - $nodeLeft + 1;
//        $shift = $nodeRight - $nodeLeft + 1;
//        $nodeToHole = $edge - $rightBoundary + $shift;
//        $this->_sync($nodeToEdge, '+', "BETWEEN {$nodeLeft} AND {$nodeRight}");
//        $this->_sync($shift, '-', "BETWEEN {$leftBoundary} AND {$rightBoundary}");
//        $this->_sync($nodeToHole, '-', "> {$edge}");
//
//        $node->set($left, $targetRight - ($nodeRight - $nodeLeft));
//        $node->set($right, $targetRight);
//
//        $node->dirty($left, false);
//        $node->dirty($right, false);
//
//        return $node;
//    }

    /**
     * Recovers the sort column values out of the current sort and id.
     *
     * @return void
     */
    public function recover()
    {
        $this->_table->getConnection()->transactional(function () {
            $this->_recover();
        });
    }

    /**
     * Method used to recover a sort
     *
     * @return void
     */
    protected function _recover()
    {
        $config = $this->getConfig();
        $primaryKey = $this->_getPrimaryKey();
        $aliasedPrimaryKey = $this->_table->aliasField($primaryKey);
        $order = $config['recoverSort'] ?: $aliasedPrimaryKey;
        $fields = [$aliasedPrimaryKey];
        if($config['foreignKey']) {
            $fields[] = $this->_table->aliasField($config['foreignKey']);
        }

        $query = $this->_scope($this->_table->query())
            ->select($fields);
        if($config['recoverUseSortField']) {
            $query = $query->order($config['sortField']);
        }
        $query = $query->order($order);

        $items = new Collection($query);
        if($config['foreignKey']) {
            $items = $items->groupBy($config['foreignKey']);
            foreach ($items->toArray() as $itemGroup) {
                $this->setNewSort($itemGroup);
            }
        } else {
            $this->setNewSort($items);
        }
    }

    /**
     * Returns the maximum sort value in the table.
     *
     * @param $foreignKeyValue
     * @return int
     */
    protected function _getMax($foreignKeyValue = null)
    {
        $field = $this->_config['sortField'];
        $maxEntity = $this->_scope($this->_table->find(), $foreignKeyValue)
            ->select([$field])
            ->orderDesc($field)
            ->first();

        if (empty($maxEntity->{$field})) {
            return 0;
        }

        return $maxEntity->{$field};
    }

    /**
     * Alters the passed query so that it only returns scoped records as defined
     * in the tree configuration.
     *
     * @param \Cake\ORM\Query $query the Query to modify
     * @param null $foreignKeyValue
     * @return Query
     */
    protected function _scope($query, $foreignKeyValue = null)
    {
        $config = $this->getConfig();

        if (is_array($config['scope'])) {
            $query = $query->where($config['scope']);
        }
        if (is_callable($config['scope'])) {
            $query = $config['scope']($query);
        }
        if($config['foreignKey'] && $foreignKeyValue != null) {
            $query = $query->where([$config['foreignKey'] => $foreignKeyValue]);
        }

        return $query;
    }

    /**
     * Ensures that the provided entity contains non-empty values for the left and
     * right fields
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to ensure fields for
     * @return void
     */
    protected function _ensureFields($entity)
    {
        $config = $this->getConfig();
        $field = $config['sortField'];
        $value = array_filter($entity->extract([$field]));
        if (count($value) === count($field)) {
            return;
        }

        $fresh = $this->_table->get($entity->get($this->_getPrimaryKey()), $field);
        $entity->set($fresh->extract([$field]), ['guard' => false]);
        $entity->dirty($field, false);
    }

    /**
     * Returns a single string value representing the primary key of the attached table
     *
     * @return string
     */
    protected function _getPrimaryKey()
    {
        if (!$this->_primaryKey) {
            $primaryKey = (array)$this->_table->getPrimaryKey();
            $this->_primaryKey = $primaryKey[0];
        }

        return $this->_primaryKey;
    }

    protected function _getForeignKeyValue($entity)
    {
        $config = $this->getConfig();
        if($config['foreignKey'] && $entity->has($config['foreignKey'])) {
            return $entity->get($config['foreignKey']);
        } else {
            return null;
        }
    }

    /**
     * @param $items
     */
    public function setNewSort($items)
    {
        $config = $this->getConfig();

        $i = 0;
        foreach ($items as $item) {
            if(isset($item->id)) {
                $i++;
                //TODO add scope and foreignKey
                $this->_table->query()
                    ->update()
                    ->set([$config['sortField'] => $i])
                    ->where(['id' => $item->id])
                    ->execute();
            }
        }
    }
}

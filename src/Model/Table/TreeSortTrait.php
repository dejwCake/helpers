<?php
namespace DejwCake\Helpers\Model\Table;

use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\MethodNotAllowedException;

/**
 * Contains a change enable status method aimed to help managing enabled status.
 */
trait TreeSortTrait
{
    /**
     * @param $items
     */
    public function setNewTreeSort($items)
    {
        if(!$this->hasBehavior('Tree')) {
            throw new MethodNotAllowedException(__d('dejw_cake_helpers', 'Model has to have Tree Behavior to use this.'));
        }

        if (!is_array($items)) {
            throw new BadRequestException(__d('dejw_cake_helpers', 'You must pass an array to setNewTreeSort.'));
        }

        foreach ($items as $item) {
            if(isset($item->id)) {
                //TODO get fields from behavior
                $this->query()
                    ->update()
                    ->set(['parent_id' => $item->parent_id, 'lft' => $item->left, 'rght' => $item->right])
                    ->where(['id' => $item->id])
                    ->execute();
            }
        }
        $this->recover();
    }
}

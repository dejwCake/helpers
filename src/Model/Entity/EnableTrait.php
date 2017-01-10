<?php
namespace DejwCake\Helpers\Model\Entity;

/**
 * Contains a change enable status method aimed to help managing enabled status.
 */
trait EnableTrait
{
    public function changeEnableStatus() {
        $this->set('enabled', !$this->get('enabled'));
    }
}

<?php
namespace DejwCake\Helpers\Model\Behavior;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class SluggableBehavior extends Behavior
{

    protected $_defaultConfig = [
        'field' => 'title',
        'slug' => 'slug',
        'replacement' => '-',
        'implementedFinders' => [
            'slug' => 'findSlug',
        ]
    ];

    public function slug(Entity $entity)
    {
        $config = $this->config();
        $value = $entity->get($config['field']);
        $entity->set($config['slug'], mb_strtolower(Text::slug($value, $config['replacement'])));
        if($table = TableRegistry::get($entity->source())) {
            if ($table->hasBehavior('Translate')) {
                $fields = $table->behaviors()->get('Translate')->config('fields');
                if(in_array('slug', $fields) && !empty($translations = $entity->get('_translations'))) {
                    foreach ($translations as $locale => $translatedEntity) {
                        $translatedValue = $translatedEntity->get($config['field']);
                        $translatedEntity->set($config['slug'], mb_strtolower(Text::slug($translatedValue, $config['replacement'])));
                    }
                }
            }
        }
    }

    public function beforeSave(Event $event, EntityInterface $entity)
    {
        $this->slug($entity);
    }

    public function findSlug(Query $query, array $options)
    {
        return $query->where(['slug' => $options['slug']]);
//        debug($this->_table);die;
//        return $query->where([$this->_table->translationField('slug')  => $options['slug']]);
    }
}

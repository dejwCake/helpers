<?php
namespace DejwCake\Helpers\Model\Behavior;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Text;

class SluggableBehavior extends Behavior
{

    /**
     * Table instance.
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

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
        if ($this->_table->hasBehavior('Translate')) {
            $fields = $this->_table->behaviors()->get('Translate')->config('fields');
            if(in_array('slug', $fields) && !empty($translations = $entity->get('_translations'))) {
                foreach ($translations as $locale => $translatedEntity) {
                    $translatedValue = $translatedEntity->get($config['field']);
                    $translatedEntity->set($config['slug'], mb_strtolower(Text::slug($translatedValue, $config['replacement'])));
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
        if ($this->_table->hasBehavior('Translate')) {
            $fields = $this->_table->behaviors()->get('Translate')->config('fields');
            if (in_array('slug', $fields)) {
                if (I18n::locale() == Configure::read('App.defaultLocale')) {
                    return $query->where(['slug' => $options['slug']]);
                } else {
                    return $query->where([$this->_table->translationField('slug') => $options['slug']]);
                }
            }
        }
        return $query->where(['slug' => $options['slug']]);
    }
}

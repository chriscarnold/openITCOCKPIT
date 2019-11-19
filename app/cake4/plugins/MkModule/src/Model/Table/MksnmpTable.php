<?php

namespace MkModule\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Mksnmp Model
 *
 * @property \MkModule\Model\Table\HostsTable|\Cake\ORM\Association\BelongsTo $Hosts
 *
 * @method \MkModule\Model\Entity\Mksnmp get($primaryKey, $options = [])
 * @method \MkModule\Model\Entity\Mksnmp newEntity($data = null, array $options = [])
 * @method \MkModule\Model\Entity\Mksnmp[] newEntities(array $data, array $options = [])
 * @method \MkModule\Model\Entity\Mksnmp|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \MkModule\Model\Entity\Mksnmp|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \MkModule\Model\Entity\Mksnmp patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \MkModule\Model\Entity\Mksnmp[] patchEntities($entities, array $data, array $options = [])
 * @method \MkModule\Model\Entity\Mksnmp findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class MksnmpTable extends Table {

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config) :void {
        parent::initialize($config);

        $this->setTable('mksnmp');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Hosts', [
            'foreignKey' => 'host_id',
            'joinType'   => 'INNER',
            'className'  => 'MkModule.Hosts'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator) :Validator {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->integer('version')
            ->allowEmpty('version');

        $validator
            ->scalar('community')
            ->maxLength('community', 255)
            ->allowEmpty('community');

        $validator
            ->integer('security_level')
            ->allowEmpty('security_level');

        $validator
            ->integer('hash_algorithm')
            ->allowEmpty('hash_algorithm');

        $validator
            ->scalar('username')
            ->maxLength('username', 255)
            ->allowEmpty('username');

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->allowEmpty('password');

        $validator
            ->integer('encryption_algorithm')
            ->allowEmpty('encryption_algorithm');

        $validator
            ->scalar('encryption_password')
            ->maxLength('encryption_password', 255)
            ->allowEmpty('encryption_password');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules) :RulesChecker {
        $rules->add($rules->isUnique(['username']));
        $rules->add($rules->existsIn(['host_id'], 'Hosts'));

        return $rules;
    }
}

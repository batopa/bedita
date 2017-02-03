<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace BEdita\API\Controller;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Controller for `/objects` endpoint.
 *
 * @since 4.0.0
 *
 * @property \BEdita\Core\Model\Table\ObjectsTable $Objects
 */
class ObjectsController extends AppController
{
    /**
     * {@inheritDoc}
     */
    public $modelClass = 'Objects';

    /**
     * The referred object type entity filled when `object_type` request param is set and valid
     *
     * @var \Cake\ORM\EntityInterface
     */
    protected $objectType = null;

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        $type = $this->request->param('object_type');
        if (!empty($type)) {
            try {
                $this->objectType = TableRegistry::get('ObjectTypes')->get($type);
                $this->Objects = TableRegistry::get($this->objectType->alias);
            } catch (RecordNotFoundException $e) {
                throw new NotFoundException('Endpoint does not exist');
            }
        }

        $this->set('_type', 'objects');
    }

    /**
     * Paginated objects list.
     *
     * @return void
     */
    public function index()
    {
        $query = $this->Objects->find('all')
            ->where(['deleted' => 0])
            ->contain(['ObjectTypes']);

        $objects = $this->paginate($query);

        $this->set(compact('objects'));
        $this->set('_serialize', ['objects']);
    }

    /**
     * Get single object data.
     *
     * @param int $id Object ID.
     * @return void
     */
    public function view($id)
    {
        $object = $this->Objects->get($id, [
            'contain' => ['ObjectTypes'],
            'conditions' => ['deleted' => 0]
        ]);

        $this->set(compact('object'));
        $this->set('_serialize', ['object']);
    }
}

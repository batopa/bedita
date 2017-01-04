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

namespace BEdita\Core\Test\Fixture;

use BEdita\Core\TestSuite\Fixture\TestFixture;

/**
 * EndpointsFixture
 *
 * @since 4.0.0
 */
class EndpointsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'name' => 'auth',
            'description' => '/auth endpoint',
            'created' => '2016-11-07 13:32:25',
            'modified' => '2016-11-07 13:32:25',
            'enabled' => 1,
            'object_type_id' => null
        ],
        [
            'name' => 'home',
            'description' => '/home endpoint',
            'created' => '2016-11-07 13:32:26',
            'modified' => '2016-11-07 13:32:26',
            'enabled' => 1,
            'object_type_id' => null
        ],
        [
            'name' => 'users',
            'description' => '/users endpoint',
            'created' => '2016-11-07 13:32:27',
            'modified' => '2016-11-07 13:32:27',
            'enabled' => 1,
            'object_type_id' => 3
        ],
        [
            'name' => 'roles',
            'description' => '/roles endpoint',
            'created' => '2016-11-07 13:32:28',
            'modified' => '2016-11-07 13:32:28',
            'enabled' => 1,
            'object_type_id' => null
        ],
        [
            'name' => 'object_types',
            'description' => '/object_types endpoint',
            'created' => '2016-11-07 13:32:29',
            'modified' => '2016-11-07 13:32:29',
            'enabled' => 1,
            'object_type_id' => null
        ],
        [
            'name' => 'objects',
            'description' => '/objects endpoint',
            'created' => '2016-11-07 13:32:30',
            'modified' => '2016-11-07 13:32:30',
            'enabled' => 1,
            'object_type_id' => null
        ],
    ];
}

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

namespace BEdita\Core\ORM\Locator;

use Cake\Core\App;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\TableLocator as CakeLocator;
use Cake\Utility\Inflector;

/**
 * Custom table locator for BEdita.
 *
 * @since 4.0.0
 */
class TableLocator extends CakeLocator
{
    use LogTrait;

    /**
     * Gets the table class name.
     *
     * @param string $alias The alias name you want to get.
     * @param array $options Table options array.
     * @return string
     */
    protected function _getClassName($alias, array $options = [])
    {
        $className = App::className($options['className'], 'Model/Table', 'Table');
        if ($className !== false) {
            return $className;
        }

        $options['className'] = sprintf('BEdita/Core.%s', $options['className']);

        $className = App::className($options['className'], 'Model/Table', 'Table');
        if ($className !== false) {
            return $className;
        }

        try {
            $objectTypes = $this->get('ObjectTypes');
            $objectType = $objectTypes->get($alias);
            $options['className'] = $objectType->table;
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'warning');
        }

        return App::className($options['className'], 'Model/Table', 'Table');
    }
}

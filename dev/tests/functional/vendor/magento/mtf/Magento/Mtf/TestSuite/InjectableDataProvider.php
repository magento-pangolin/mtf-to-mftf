<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\TestSuite;

use Magento\Mtf\ObjectManager;

/**
 * Class InjectableDataProvider
 *
 * @api
 */
class InjectableDataProvider extends InjectableMethod
{
    /**
     * @constructor
     * @param string $class
     * @param string $name
     * @param string $path
     */
    public function __construct($class = '', $name = '', $path = '')
    {
        $this->initObjectManager();
        $this->setName($class);
    }
}

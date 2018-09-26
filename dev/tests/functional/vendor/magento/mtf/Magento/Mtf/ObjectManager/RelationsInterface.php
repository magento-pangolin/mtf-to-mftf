<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Mtf\ObjectManager;

/**
 * Interface RelationsInterface
 */
interface RelationsInterface
{
    /**
     * Check whether requested type is available for read
     *
     * @param string $type
     * @return bool
     */
    public function has($type);

    /**
     * Retrieve list of parents
     *
     * @param string $type
     * @return array
     */
    public function getParents($type);
}

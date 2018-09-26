<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\Util\Iterator;

use Magento\Mtf\ObjectManager;

/**
 * Class AbstractIterator
 *
 * @api
 */
abstract class AbstractIterator implements \Iterator, \Countable
{
    /**
     * Data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Current data element
     *
     * @var mixed
     */
    protected $current;

    /**
     * Key associated with the current row data
     *
     * @var int|string
     */
    protected $key;

    /**
     * Get current element
     *
     * @return mixed
     */
    abstract public function current();

    /**
     * Check if current element is valid
     *
     * @return boolean
     */
    abstract protected function isValid();

    /**
     * Initialize Data Array
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->data);
        if (!$this->isValid()) {
            $this->next();
        }
    }

    /**
     * Seek to next valid row
     *
     * @return void
     */
    public function next()
    {
        $this->current = next($this->data);

        if ($this->current !== false) {
            if (!$this->isValid()) {
                $this->next();
            }
        } else {
            $this->key = null;
        }
    }

    /**
     * Check if current position is valid
     *
     * @return boolean
     */
    public function valid()
    {
        $current = current($this->data);
        if ($current === false || $current === null) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get data key of the current data element
     *
     * @return int|string
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * To make iterator countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Initialize first element
     *
     * @return void
     */
    protected function initFirstElement()
    {
        if ($this->data) {
            $this->current = reset($this->data);
            if (!$this->isValid()) {
                $this->next();
            }
        }
    }
}

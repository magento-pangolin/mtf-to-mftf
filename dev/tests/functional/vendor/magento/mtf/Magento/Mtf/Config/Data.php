<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\Config;

/**
 * Class Data
 */
class Data implements \Magento\Mtf\Config\DataInterface
{
    /**
     * Configuration reader model
     *
     * @var \Magento\Mtf\Config\ReaderInterface
     */
    protected $reader;

    /**
     * Config data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     *
     * @param \Magento\Mtf\Config\ReaderInterface $reader
     */
    public function __construct(\Magento\Mtf\Config\ReaderInterface $reader)
    {
        $this->reader = $reader;
        $this->load();
    }

    /**
     * Merge config data to the object
     *
     * @param array $config
     * @return void
     */
    public function merge(array $config)
    {
        $this->data = array_replace_recursive($this->data, $config);
    }

    /**
     * Get config value by key
     *
     * @param string $path
     * @param mixed $default
     * @return array|mixed|null
     */
    public function get($path = null, $default = null)
    {
        if ($path === null) {
            return $this->data;
        }
        $keys = explode('/', $path);
        $data = $this->data;
        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return $default;
            }
        }
        return $data;
    }

    /**
     * Set name of the config file
     *
     * @param string $fileName
     * @return self
     */
    public function setFileName($fileName)
    {
        if (!is_null($fileName)) {
            $this->reader->setFileName($fileName);
        }
        return $this;
    }

    /**
     * Load config data
     *
     * @param string|null $scope
     */
    public function load($scope = null)
    {
        $this->merge(
            $this->reader->read($scope)
        );
    }
}
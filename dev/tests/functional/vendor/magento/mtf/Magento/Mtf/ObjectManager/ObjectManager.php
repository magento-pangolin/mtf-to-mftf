<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\ObjectManager;

/**
 * Class ObjectManager
 */
class ObjectManager implements \Magento\Mtf\ObjectManagerInterface
{
    /**
     * @var \Magento\Mtf\ObjectManager\FactoryInterface
     */
    protected $_factory;

    /**
     * List of shared instances
     *
     * @var array
     */
    protected $_sharedInstances = [];

    /**
     * @var Config\Config
     */
    protected $_config;

    /**
     * @param FactoryInterface $factory
     * @param ConfigInterface $config
     * @param array $sharedInstances
     */
    public function __construct(FactoryInterface $factory, ConfigInterface $config, array $sharedInstances = [])
    {
        $this->_config = $config;
        $this->_factory = $factory;
        $this->_sharedInstances = $sharedInstances;
        $this->_sharedInstances['Magento\Mtf\ObjectManagerInterface'] = $this;
    }

    /**
     * Create new object instance
     *
     * @param string $type
     * @param array $arguments
     * @return mixed
     */
    public function create($type, array $arguments = [])
    {
        return $this->_factory->create($this->_config->getPreference($type), $arguments);
    }

    /**
     * Retrieve cached object instance
     *
     * @param string $type
     * @return mixed
     */
    public function get($type)
    {
        $type = $this->_config->getPreference($type);
        if (!isset($this->_sharedInstances[$type])) {
            $this->_sharedInstances[$type] = $this->_factory->create($type);
        }
        return $this->_sharedInstances[$type];
    }

    /**
     * Configure di instance
     *
     * @param array $configuration
     * @return void
     */
    public function configure(array $configuration)
    {
        $this->_config->extend($configuration);
    }

    /**
     * Avoid to serialize Closure properties
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }
}

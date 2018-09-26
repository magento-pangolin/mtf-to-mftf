<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Mtf\ObjectManager\Config;

use Magento\Mtf\ObjectManager\DefinitionInterface;
use Magento\Mtf\ObjectManager\RelationsInterface;

/**
 * Class Config
 */
class Config implements \Magento\Mtf\ObjectManager\ConfigInterface
{
    /**
     * Class definitions
     *
     * @var \Magento\Mtf\ObjectManager\DefinitionInterface
     */
    protected $_definitions;

    /**
     * Current cache key
     *
     * @var string
     */
    protected $_currentCacheKey;

    /**
     * Interface preferences
     *
     * @var array
     */
    protected $_preferences = [];

    /**
     * Virtual types
     *
     * @var array
     */
    protected $_virtualTypes = [];

    /**
     * Instance arguments
     *
     * @var array
     */
    protected $_arguments = [];

    /**
     * Type shareability
     *
     * @var array
     */
    protected $_nonShared = [];

    /**
     * List of relations
     *
     * @var RelationsInterface
     */
    protected $_relations;

    /**
     * List of merged arguments
     *
     * @var array
     */
    protected $_mergedArguments;

    /**
     * @param RelationsInterface $relations
     * @param DefinitionInterface $definitions
     */
    public function __construct(RelationsInterface $relations = null, DefinitionInterface $definitions = null)
    {
        $this->_relations = $relations ? : new \Magento\Mtf\ObjectManager\Relations\Runtime();
        $this->_definitions = $definitions ? : new \Magento\Mtf\ObjectManager\Definition\Runtime();
    }

    /**
     * Retrieve list of arguments per type
     *
     * @param string $type
     * @return array
     */
    public function getArguments($type)
    {
        return isset($this->_mergedArguments[$type])
            ? $this->_mergedArguments[$type]
            : $this->_collectConfiguration($type);
    }

    /**
     * Check whether type is shared
     *
     * @param string $type
     * @return bool
     */
    public function isShared($type)
    {
        return !isset($this->_nonShared[$type]);
    }

    /**
     * Retrieve instance type
     *
     * @param string $instanceName
     * @return mixed
     */
    public function getInstanceType($instanceName)
    {
        while (isset($this->_virtualTypes[$instanceName])) {
            $instanceName = $this->_virtualTypes[$instanceName];
        }
        return $instanceName;
    }

    /**
     * Retrieve preference for type
     *
     * @param string $type
     * @return string
     * @throws \LogicException
     */
    public function getPreference($type)
    {
        $type = ltrim($type, '\\');
        $preferencePath = [];
        while (isset($this->_preferences[$type])) {
            if (isset($preferencePath[$this->_preferences[$type]])) {
                throw new \LogicException(
                    'Circular type preference: ' .
                    $type .
                    ' relates to ' .
                    $this->_preferences[$type] .
                    ' and viceversa.'
                );
            }
            $type = $this->_preferences[$type];
            $preferencePath[$type] = 1;
        }
        return $type;
    }

    /**
     * Collect parent types configuration for requested type
     *
     * @param string $type
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _collectConfiguration($type)
    {
        if (!isset($this->_mergedArguments[$type])) {
            if (isset($this->_virtualTypes[$type])) {
                $arguments = $this->_collectConfiguration($this->_virtualTypes[$type]);
            } else {
                if ($this->_relations->has($type)) {
                    $relations = $this->_relations->getParents($type);
                    $arguments = [];
                    foreach ($relations as $relation) {
                        if ($relation) {
                            $relationArguments = $this->_collectConfiguration($relation);
                            if ($relationArguments) {
                                $arguments = array_replace($arguments, $relationArguments);
                            }
                        }
                    }
                } else {
                    $arguments = [];
                }
            }

            if (isset($this->_arguments[$type])) {
                if ($arguments && count($arguments)) {
                    $arguments = array_replace_recursive($arguments, $this->_arguments[$type]);
                } else {
                    $arguments = $this->_arguments[$type];
                }
            }
            $this->_mergedArguments[$type] = $arguments;
            return $arguments;
        }
        return $this->_mergedArguments[$type];
    }

    /**
     * Merge configuration
     *
     * @param array $configuration
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _mergeConfiguration(array $configuration)
    {
        foreach ($configuration as $key => $curConfig) {
            switch ($key) {
                case 'preferences':
                    foreach ($curConfig as $for => $to) {
                        $this->_preferences[ltrim($for, '\\')] = ltrim($to, '\\');
                    }
                    break;

                default:
                    $key = ltrim($key, '\\');
                    if (isset($curConfig['type'])) {
                        $this->_virtualTypes[$key] = ltrim($curConfig['type'], '\\');
                    }
                    if (isset($curConfig['arguments'])) {
                        if (!empty($this->_mergedArguments)) {
                            $this->_mergedArguments = [];
                        }
                        if (isset($this->_arguments[$key])) {
                            $this->_arguments[$key] = array_replace($this->_arguments[$key], $curConfig['arguments']);
                        } else {
                            $this->_arguments[$key] = $curConfig['arguments'];
                        }
                    }
                    if (isset($curConfig['shared'])) {
                        if (!$curConfig['shared']) {
                            $this->_nonShared[$key] = 1;
                        } else {
                            unset($this->_nonShared[$key]);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Extend configuration
     *
     * @param array $configuration
     * @return void
     */
    public function extend(array $configuration)
    {
        $this->_mergeConfiguration($configuration);
    }
}

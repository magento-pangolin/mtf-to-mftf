<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Mtf\Config;

use Magento\Mtf\ObjectManager\Config\Mapper\ArgumentParser;
use Magento\Mtf\Data\Argument\InterpreterInterface;

/**
 * Converter for configuration data.
 */
class Converter implements \Magento\Mtf\Config\ConverterInterface
{
    /**
     * Unique identifier of node.
     */
    const NAME_ATTRIBUTE = 'name';

    /**
     * @var ArgumentParser
     */
    protected $argumentParser;

    /**
     * @var InterpreterInterface
     */
    protected $argumentInterpreter;

    /**
     * @var string
     */
    protected $argumentNodeName;

    /**
     * @var string[]
     */
    protected $idAttributes;

    /**
     * @param ArgumentParser $argumentParser
     * @param InterpreterInterface $argumentInterpreter
     * @param string $argumentNodeName
     * @param string[] $idAttributes
     */
    public function __construct(
        ArgumentParser $argumentParser,
        InterpreterInterface $argumentInterpreter,
        $argumentNodeName,
        array $idAttributes = []
    ) {
        $this->argumentParser = $argumentParser;
        $this->argumentInterpreter = $argumentInterpreter;
        $this->argumentNodeName = $argumentNodeName;
        $this->idAttributes = $idAttributes;
    }

    /**
     * Convert XML to array.
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert(\DOMDocument $source)
    {
        return $this->convertXml($source->documentElement->childNodes);
    }

    /**
     * Convert XML node to array or string recursive.
     *
     * @param \DOMNodeList|array $elements
     * @return array
     */
    protected function convertXml($elements)
    {
        $result = [];

        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                if ($element->hasAttribute('xsi:type')) {
                    if ($element->hasAttribute('path')) {
                        $elementData = $this->getAttributes($element);
                        $elementData['value'] = $this->argumentInterpreter->evaluate(
                            $this->argumentParser->parse($element)
                        );
                        unset($elementData['xsi:type'], $elementData['item']);
                    } else {
                        $elementData = $this->argumentInterpreter->evaluate(
                            $this->argumentParser->parse($element)
                        );
                    }
                } else {
                    $elementData = array_merge(
                        $this->getAttributes($element),
                        $this->getChildNodes($element)
                    );
                }
                $key = $this->getElementKey($element);
                if ($key) {
                    $result[$element->nodeName][$key] = $elementData;
                } elseif (!empty($elementData)) {
                    $result[$element->nodeName][] = $elementData;
                }
            } elseif ($element->nodeType == XML_TEXT_NODE && trim($element->nodeValue) != '') {
                return ['value' => $element->nodeValue];
            }
        }

        return $result;
    }

    /**
     * Get key for DOM element
     *
     * @param \DOMElement $element
     * @return bool|string
     */
    protected function getElementKey(\DOMElement $element)
    {
        if (isset($this->idAttributes[$element->nodeName])) {
            if ($element->hasAttribute($this->idAttributes[$element->nodeName])) {
                return $element->getAttribute($this->idAttributes[$element->nodeName]);
            }
        }
        if ($element->hasAttribute(self::NAME_ATTRIBUTE)) {
            return $element->getAttribute(self::NAME_ATTRIBUTE);
        }
        return false;
    }

    /**
     * @param \DOMElement $element
     * @param \DOMAttr $attribute
     * @return bool
     */
    protected function isKeyAttribute(\DOMElement $element, \DOMAttr $attribute)
    {
        if (isset($this->idAttributes[$element->nodeName])) {
            return $attribute->name == $this->idAttributes[$element->nodeName];
        } else {
            return $attribute->name == self::NAME_ATTRIBUTE;
        }
    }

    /**
     * Get node attributes.
     *
     * @param \DOMElement $element
     * @return array
     */
    protected function getAttributes(\DOMElement $element)
    {
        $attributes = [];
        if ($element->hasAttributes()) {
            /** @var \DomAttr $attribute */
            foreach ($element->attributes as $attribute) {
                if (trim($attribute->nodeValue) != '' && !$this->isKeyAttribute($element, $attribute)) {
                    $attributes[$attribute->nodeName] = $this->castNumeric($attribute->nodeValue);
                }
            }
        }
        return $attributes;
    }

    /**
     * Get child nodes data.
     *
     * @param \DOMElement $element
     * @return array
     */
    protected function getChildNodes(\DOMElement $element)
    {
        $children = [];
        if ($element->hasChildNodes()) {
            $children = $this->convertXml($element->childNodes);
        }
        return $children;
    }

    /**
     * Cast nodeValue to int or double.
     *
     * @param string $nodeValue
     * @return float|int
     */
    protected function castNumeric($nodeValue)
    {
        if (is_numeric($nodeValue)) {
            if (preg_match('/^\d+$/', $nodeValue)) {
                $nodeValue = (int) $nodeValue;
            } else {
                $nodeValue = (double) $nodeValue;
            }
        }

        return $nodeValue;
    }
}

<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf;


use Magento\Mtf\Client\ElementInterface;
use Magento\Mtf\Client\Locator;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Selenium Driver.
 */
class Driver extends \Magento\Mtf\Client\Driver\Selenium\Driver
{
    /**
     * @var \Magento\Mtf\MftfGenerator
     */
    private $MFTF_TEST_GENERATOR;

    /**
     * Initial web driver.
     *
     * @return void
     */
    protected function init()
    {
        parent::init();
        $this->MFTF_TEST_GENERATOR = new \Magento\Mtf\MftfGenerator("Test");
    }

    /**
     * @param ElementInterface $element
     * @return string
     */
    private function getElementForMftf(ElementInterface $element)
    {
        /**
         * @var CssSelectorConverter $cssConverter
         */
        $cssConverter = new CssSelectorConverter();
        $chainElements = [$element];
        while ($element = $element->getContext()) {
            $chainElements[] = $element;
        }

        $contextElement = '';
        /** @var ElementInterface $context */
        foreach (array_reverse($chainElements) as $chainElement) {
            /** @var ElementInterface $chainElement */
            if ($chainElement->getLocator()->container['using'] == Locator::SELECTOR_XPATH) {
                $contextElement .= $chainElement->getLocator()->container['value'];
                continue;
            }
            $contextElement .= $cssConverter->toXPath($chainElement->getLocator()->container['value'], "//");
        }

        $contextElement = str_replace(".//", "//", $contextElement);
//        $contextElement = $this->xpathEscape($contextElement);

        return $contextElement;
    }

    /**
     * Escape single and/or double quotes in XPath selector by concat()
     *
     * @param string $query
     * @param string $defaultDelim [optional]
     * @return string
     */
    protected function xpathEscape($query, $defaultDelim = '"')
    {
        if (strpos($query, $defaultDelim) === false) {
            return $defaultDelim . $query . $defaultDelim;
        }
        preg_match_all("#(?:('+)|[^']+)#", $query, $matches);
        list($parts, $apos) = $matches;
        $delim = '';
        foreach ($parts as $i => &$part) {
            $delim = $apos[$i] ? '"' : "'";
            $part = $delim . $part . $delim;
        }
        if (count($parts) == 1) {
            $parts[] = $delim . $delim;
        }

        return 'concat(' . implode(',', $parts) . ')';
    }

    /**
     * Click.
     *
     * @param ElementInterface $element
     * @return void
     */
    public function click(ElementInterface $element)
    {
        parent::click($element);

        $contextElement = $this->getElementForMftf($element);
        if (strpos($contextElement, "grid-filter-reset") !== false) {
            $this->MFTF_TEST_GENERATOR->addAction('<conditionalClick selector="{{AdminDataGridHeaderSection.clearFilters}}" dependentSelector="{{AdminDataGridHeaderSection.clearFilters}}" visible="true" stepKey="%%StepKey%%"/>');
        } else {
            $this->MFTF_TEST_GENERATOR->addAction('<click selector="' . $contextElement . '" stepKey="%%StepKey%%"/>');
        }
    }

    /**
     * Double click.
     *
     * @param ElementInterface $element
     * @return void
     */
    public function doubleClick(ElementInterface $element)
    {
        parent::doubleClick($element);
        $contextElement = $this->getElementForMftf($element);
        $this->MFTF_TEST_GENERATOR->addAction('<doubleClick selector="' . $contextElement . '" stepKey="%%StepKey%%"/>');
    }

    /**
     * Right click.
     *
     * @param ElementInterface $element
     * @return void
     */
    public function rightClick(ElementInterface $element)
    {
        parent::rightClick($element);
        $contextElement = $this->getElementForMftf($element);
        $this->MFTF_TEST_GENERATOR->addAction('<clickWithRightButton selector="' . $contextElement . '" stepKey="%%StepKey%%"/>');
    }

    /**
     * Check whether element is present in the DOM.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isPresent(ElementInterface $element)
    {
        $result = parent::isPresent($element);

        $contextElement = $this->getElementForMftf($element);
        $this->MFTF_TEST_GENERATOR->addAction('<seeElement selector="' . $contextElement . '" stepKey="%%StepKey%%" />');


        return $result;
    }

    /**
     * Check whether element is visible.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isVisible(ElementInterface $element)
    {
        $result = parent::isVisible($element);

        $contextElement = $this->getElementForMftf($element);
        $this->MFTF_TEST_GENERATOR->addAction('<seeElement selector="' . $contextElement . '" stepKey="%%StepKey%%" />');

        return $result;
    }

    /**
     * Set the value.
     *
     * @param ElementInterface $element
     * @param string|array $value
     * @return void
     */
    public function setValue(ElementInterface $element, $value)
    {
        parent::setValue($element, $value);

        $contextElement = $this->getElementForMftf($element);

        if (is_array($value)) {
            $value = "[" . implode(", ", $value) . "]";
        }
        $this->MFTF_TEST_GENERATOR->addAction('<fillField selector="' . $contextElement . '" userInput="' . $value . '" stepKey="%%StepKey%%"/>');
    }

    /**
     * Get the value.
     *
     * @param ElementInterface $element
     * @return null|string
     */
    public function getValue(ElementInterface $element)
    {
        $result = parent::getValue($element);

        return $result;
    }

    /**
     * Get content.
     *
     * @param ElementInterface $element
     * @return string
     */
    public function getText(ElementInterface $element)
    {
        return $this->getNativeElement($element)->text();
    }

    /**
     * Drag and drop element to(between) another element(s).
     *
     * @param ElementInterface $element
     * @param ElementInterface $target
     * @return void
     */
    public function dragAndDrop(ElementInterface $element, ElementInterface $target)
    {
        parent::dragAndDrop($element, $target);
    }

    /**
     * Hover mouse over an element.
     *
     * @param ElementInterface $element
     * @return void
     */
    public function hover(ElementInterface $element)
    {
        parent::hover($element);
    }

    /**
     * Send a sequence of key strokes to the active element.
     *
     * @param ElementInterface $element
     * @param array $keys
     * @return void
     */
    public function keys(ElementInterface $element, array $keys)
    {
        parent::keys($element, $keys);
    }

    /**
     * Press a modifier key.
     *
     * @param string $key
     * @param bool $isSpecialKey
     * @return void
     */
    public function pressKey($key, $isSpecialKey = false)
    {
        parent::pressKey($key, $isSpecialKey);
    }

    /**
     * Wait until callback isn't null or timeout occurs.
     *
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public function waitUntil($callback)
    {
        return $this->driver->waitUntil($callback);
    }

    /**
     * Get the value of a the given attribute of the element.
     *
     * @param ElementInterface $element
     * @param string $name
     * @return string
     */
    public function getAttribute(ElementInterface $element, $name)
    {
        return $this->getNativeElement($element)->attribute($name);
    }

    /**
     * Open page.
     *
     * @param string $url
     * @return void
     */
    public function open($url)
    {
        parent::open($url);

        $url = str_replace("http://magento.local/", "" , $url);

        $this->MFTF_TEST_GENERATOR->addAction('<amOnPage url="' . $url . '" stepKey="%%StepKey%%"/>');
    }

    /**
     * Back to previous page.
     *
     * @return void
     */
    public function back()
    {
        parent::back();
    }

    /**
     * Forward page.
     *
     * @return void
     */
    public function forward()
    {
        parent::forward();
    }

    /**
     * Refresh page.
     *
     * @return void
     */
    public function refresh()
    {
        $this->driver->refresh();
    }

    /**
     * Reopen browser.
     *
     * @return void
     */
    public function reopen()
    {
        $this->eventManager->dispatchEvent(['reopen'], [__METHOD__]);
        if ($this->driver->getSessionId()) {
            $this->driver->stop();
        }
        if ($sessionStrategy = $this->configuration->get('server/0/item/selenium/sessionStrategy')) {
            $this->driver->setSessionStrategy($sessionStrategy);
        } else {
            $this->driver->setSessionStrategy('isolated');
        }
        $this->init();
    }

    /**
     * Change the focus to a frame in the page by locator.
     *
     * @param Locator|null $locator
     * @return void
     * @throws \Exception
     */
    public function switchToFrame(Locator $locator = null)
    {
        parent::switchToFrame($locator);

        $this->MFTF_TEST_GENERATOR->addAction('<switchToIFrame selector="' . $locator->container['value'] . '" stepKey="%%StepKey%%" />');
    }

    /**
     * Open new tab/window in Browser.
     *
     * @return void
     */
    public function openWindow()
    {
        parent::openWindow();
    }

    /**
     * Close the current window or specified one.
     *
     * @param string|null $handle [optional]
     * @return void
     */
    public function closeWindow($handle = null)
    {
        parent::closeWindow($handle);
    }

    /**
     * Get page title text.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->driver->title();
    }

    /**
     * Press OK on an alert or confirm a dialog.
     *
     * @return void
     */
    public function acceptAlert()
    {
        //$this->_driver->acceptAlert(); Temporary fix for selenium issue 3544
        $this->waitForOperationSuccess('acceptAlert');
        $this->eventManager->dispatchEvent(['accept_alert_after'], [__METHOD__]);
    }

    /**
     * Press Cancel on alert or does not confirm a dialog.
     *
     * @return void
     */
    public function dismissAlert()
    {
        //$this->_driver->dismissAlert(); Temporary fix for selenium issue 3544
        $this->waitForOperationSuccess('dismissAlert');
        $this->eventManager->dispatchEvent(['dismiss_alert_after'], [__METHOD__]);

        $this->MFTF_TEST_GENERATOR->addAction('<acceptPopup stepKey="%%StepKey%%" />');
    }

    /**
     * @todo Temporary fix for selenium issue 3544
     * https://code.google.com/p/selenium/issues/detail?id=3544
     *
     * @param string $operation
     */
    protected function waitForOperationSuccess($operation)
    {
        $driver = $this->driver;
        $this->waitUntil(
            function () use ($driver, $operation) {
                try {
                    $driver->$operation();
                } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $exception) {
                    return null;
                }
                return true;
            }
        );
    }

    /**
     * Get the alert dialog text.
     *
     * @return string
     */
    public function getAlertText()
    {
        return $this->driver->alertText();
    }

    /**
     * Set the text to a prompt popup.
     *
     * @param string $text
     * @return void
     */
    public function setAlertText($text)
    {
        $this->driver->alertText($text);
    }

    /**
     * Get current page url.
     *
     * @return string
     */
    public function getUrl()
    {
        try {
            if ($this->driver->alertText()) {
                return null;
            }
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $exception) {
            return $this->driver->url();
        }

        return $this->driver->url();
    }

    /**
     * Get Html page source.
     *
     * @return string
     */
    public function getHtmlSource()
    {
        return $this->driver->source();
    }

    /**
     * Get binary string of image.
     *
     * @return string
     */
    public function getScreenshotData()
    {
        return $this->driver->currentScreenshot();
    }

    /**
     * Set focus on element.
     *
     * @param ElementInterface $element
     * @return void
     */
    public function focus(ElementInterface $element)
    {
        $elementId = $element->getAttribute('id');
        if ($elementId) {
            $js = "if (window.jQuery != undefined) jQuery('[id=\"$elementId\"]').focus(); ";
            $js .= "var element = document.getElementById('$elementId'); if (element != undefined) element.focus();";
            $this->driver->execute(['script' => $js, 'args' => []]);
        } else {
            $element->click();
        }
    }

    /**
     * Trigger change on event.
     *
     * @param string $elementId
     * @return void
     */
    protected function triggerChangeEvent($elementId)
    {
        if ($elementId) {
            $js = "if (window.jQuery != undefined)";
            $js .= "{jQuery('[id=\"$elementId\"]').change(); jQuery('[id=\"$elementId\"]').keyup();}";
            $js .= "var element = document.getElementById('$elementId'); if (element != undefined) element.focus();";
            $this->driver->execute(['script' => $js, 'args' => []]);
        }
    }

    /**
     * Upload file.
     *
     * @param ElementInterface $element
     * @param string $path
     * @return void
     */
    public function uploadFile(ElementInterface $element, $path)
    {
        $element = $this->getNativeElement($element, false);
        $element->value($path);
    }

    public function __destruct()
    {
        $this->MFTF_TEST_GENERATOR->generateTest();
    }
}

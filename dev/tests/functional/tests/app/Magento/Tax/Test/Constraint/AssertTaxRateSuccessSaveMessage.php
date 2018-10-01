<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Tax\Test\Constraint;

use Magento\Tax\Test\Page\Adminhtml\TaxRateIndex;
use Magento\Mtf\Constraint\AbstractConstraint;

/**
 * Class AssertTaxRateSuccessSaveMessage
 */
class AssertTaxRateSuccessSaveMessage extends AbstractConstraint
{
    const SUCCESS_MESSAGE = 'You saved the tax rate.';

    /**
     * Assert that success message is displayed after tax rate saved
     *
     * @param TaxRateIndex $taxRateIndexPage
     * @return void
     */
    public function processAssert(TaxRateIndex $taxRateIndexPage)
    {
        $actualMessage = $taxRateIndexPage->getMessagesBlock()->getSuccessMessage();
        \PHPUnit\Framework\Assert::assertEquals(
            self::SUCCESS_MESSAGE,
            $actualMessage,
            'Wrong success message is displayed.'
            . "\nExpected: " . self::SUCCESS_MESSAGE
            . "\nActual: " . $actualMessage
        );

        // todo: remove below later... but I will test all asserts here

        \PHPUnit\Framework\Assert::assertArrayHasKey(
            "cat",
            ["dog" => 1, "horse" => false, "cat" => "success"],
            '3D Secure information is not present.'
        );

        \PHPUnit\Framework\Assert::assertArrayNotHasKey(
            "cat",
            ["dog" => 1, "horse" => false],
            'Product page must be without swatch attribute options'
        );

        \PHPUnit\Framework\Assert::assertContains(
            "cat",
            "dogdogDOGdogcatdogdogDOGdog",
            'Wrong content is displayed.'
        );

        \PHPUnit\Framework\Assert::assertCount(
            3,
            ["one", "two", "three"],
            'Incorrect count'
        );

        \PHPUnit\Framework\Assert::assertEmpty(
            array(),
            'Array is not empty'
        );

        \PHPUnit\Framework\Assert::assertFalse(
            1 == 2,
            'Some error message here'
        );

        \PHPUnit\Framework\Assert::assertTrue(
            1 == 1,
            'Some error message here'
        );
    }

    /**
     * Text of Created Tax Rate Success Message assert
     *
     * @return string
     */
    public function toString()
    {
        return 'Tax rate success create message is present.';
    }
}

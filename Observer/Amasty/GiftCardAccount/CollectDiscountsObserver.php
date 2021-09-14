<?php
/**
 * Bolt magento2 plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Bolt
 * @package    Bolt_ModulesSupport
 *
 * @copyright  Copyright (c) 2017-2021 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\ModulesSupport\Observer\Amasty\GiftCardAccount;

use Bolt\Boltpay\Model\ThirdParty\FilterInterface;
use Bolt\Boltpay\Helper\Bugsnag;
use Bolt\Boltpay\Helper\Discount;
use Bolt\Boltpay\Helper\Shared\CurrencyUtils;
use Magento\Framework\Event\Observer;

class CollectDiscountsObserver implements FilterInterface
{
    /**
     * @var Bugsnag
     */
    private $bugsnagHelper;
    
    /**
     * @var Discount
     */
    private $discountHelper;

    /**
     * constructor
     *
     * @param Bugsnag  $bugsnagHelper
     * @param Discount  $discountHelper
     */
    public function __construct(
        Bugsnag $bugsnagHelper,
        Discount $discountHelper
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
        $this->discountHelper = $discountHelper;
    }

    public function execute(Observer $observer)
    {
        // Get result
        $result = $observer->getResult();
        // Get send classes
        $sendClasses = $observer->getSendClasses();
        list ($giftcardAccountRepository, $giftcardQuoteRepository) = $sendClasses;
        // Get extra arguments
        $quote = $observer->getEvent()->getQuote();
        $parentQuote = $observer->getEvent()->getParentQuote();
        $paymentOnly = $observer->getEvent()->getPaymentOnly();
       
        list ($discounts, $totalAmount, $diff) = $result;
        try {
            $currencyCode = $quote->getQuoteCurrencyCode();
            /** @var \Magento\Quote\Model\Quote\Address\Total[] */
            $totals = $quote->getTotals();
            $totalDiscount = $totals[Discount::AMASTY_GIFTCARD] ?? null;
            $roundedDiscountAmount = 0;
            $discountAmount = 0;
            ///////////////////////////////////////////////////////////////////////////
            // If Amasty gift cards can be used for shipping and tax (PayForEverything)
            // accumulate all the applied gift cards balance as discount amount. If the
            // final discounts sum is greater than the cart total amount ($totalAmount < 0)
            // the "fixed_amount" type is added below.
            ///////////////////////////////////////////////////////////////////////////
            if ($totalDiscount && $totalDiscount->getValue() && $this->discountHelper->getAmastyPayForEverything()) {
                $giftcardQuote = $giftcardQuoteRepository->getByQuoteId($quote->getId());
                $discountType = $this->discountHelper->getBoltDiscountType('by_fixed');
                foreach ($giftcardQuote->getGiftCards() as $appliedGiftcardData) {
                    $giftcard = $giftcardAccountRepository->getById($appliedGiftcardData['id']);
                    $amount = abs($giftcard->getCurrentValue());
                    $roundedAmount = CurrencyUtils::toMinor($amount, $currencyCode);
                    $giftCardCode = $giftcard->getCodeModel()->getCode();
                    $discountItem = [
                        'description'       => __('Gift Card ') . $giftCardCode,
                        'amount'            => $roundedAmount,
                        'discount_category' => Discount::BOLT_DISCOUNT_CATEGORY_GIFTCARD,
                        'reference'         => $giftCardCode,
                        'discount_type'     => $discountType,
                        // For v1/discounts.code.apply and v2/cart.update
                        'type'              => $discountType,
                        // For v1/merchant/order
                    ];
                    $discountAmount += $amount;
                    $roundedDiscountAmount += $roundedAmount;
                    $discounts[] = $discountItem;
                }

                $diff -= CurrencyUtils::toMinorWithoutRounding($discountAmount, $currencyCode) - $roundedDiscountAmount;
                $totalAmount -= $roundedDiscountAmount;
            }
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        } finally {
            return [$discounts, $totalAmount, $diff];
        }
    }
}

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
use Magento\Framework\Event\Observer;

class FilterApplyingGiftCardCodeObserver implements FilterInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get result
        $result = $observer->getResult();
        // Get send classes
        $sendClasses = $observer->getSendClasses();
        list ($giftcardProcessor) = $sendClasses;
        // Get extra arguments
        $giftCard = $observer->getEvent()->getGiftCard();
        $quote = $observer->getEvent()->getQuote();

        if (!$giftCard instanceof \Amasty\GiftCardAccount\Api\Data\GiftCardAccountInterface) {
            return $result;
        }
        try {
            $giftcardProcessor->applyToCart($giftCard, $quote);
            $result = true;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            //no giftcards applied on order, safe to ignore
        }
        
        return $result;
    }
}

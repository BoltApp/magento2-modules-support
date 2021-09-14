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

namespace Bolt\ModulesSupport\Observer\Amasty\GiftCard;

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
        list ($giftCardManagement) = $sendClasses;
        // Get extra arguments
        $giftCard = $observer->getEvent()->getGiftCard();
        $quote = $observer->getEvent()->getQuote();

        if (!$giftCard instanceof \Amasty\GiftCard\Model\Account) {
            return $result;
        }
        try {
            $giftCardManagement->set($quote->getId(), $giftCard->getCode());
            return true;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return false;
        }
    }
}

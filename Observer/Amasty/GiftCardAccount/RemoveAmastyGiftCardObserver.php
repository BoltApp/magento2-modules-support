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

use Bolt\Boltpay\Helper\Bugsnag;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RemoveAmastyGiftCardObserver implements ObserverInterface
{
    /**
     * @var Bugsnag
     */
    private $bugsnagHelper;
    
    /**
     * constructor
     *
     * @param Bugsnag  $bugsnagHelper
     */
    public function __construct(
        Bugsnag $bugsnagHelper
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
    }

    public function execute(Observer $observer)
    {
        $sendClasses = $observer->getSendClasses();
        list ($amastyGiftCardAccountManagement) = $sendClasses;
        $quote = $observer->getEvent()->getQuote();
        $codeId = $observer->getEvent()->getCodeId();
        
        try {
            if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getAmGiftcardQuote()) {
                $cards = $quote->getExtensionAttributes()->getAmGiftcardQuote()->getGiftCards();
            }

            $giftCodeExists = false;
            $giftCode = '';
            foreach ($cards as $k => $card) {
                if ($card['id'] == $codeId) {
                    $giftCodeExists = true;
                    $giftCode = $card['code'];
                    break;
                }
            }
            
            if ($giftCodeExists) {
                $amastyGiftCardAccountManagement->removeGiftCardFromCart($quote->getId(), $giftCode);
            }
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }
}

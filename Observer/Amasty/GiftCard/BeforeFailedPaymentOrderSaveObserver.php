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

use Bolt\Boltpay\Helper\Bugsnag;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BeforeFailedPaymentOrderSaveObserver implements ObserverInterface
{
    /**
     * @var Bugsnag
     */
    private $bugsnagHelper;
    
    /**
     * constructor
     *
     * @param Bugsnag $bugsnagHelper
     */
    public function __construct(
        Bugsnag $bugsnagHelper
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get send classes
        $sendClasses = $observer->getSendClasses();
        list ($giftcardQuoteCollectionFactory, $giftcardCodeRepository, $giftcardAccountRepository) = $sendClasses;
        // Get extra arguments
        $order = $observer->getEvent()->getOrder();

        try {
            $giftcardQuotes = $giftcardQuoteCollectionFactory->create()
                ->getGiftCardsByQuoteId($order->getQuoteId());
            /** @var \Amasty\GiftCard\Model\Quote $giftcardQuote */
            foreach ($giftcardQuotes->getItems() as $giftcardQuote) {
                try {
                    $giftcardAccount = $giftcardAccountRepository->getById($giftcardQuote->getAccountId());
                    $giftcardCode = $giftcardCodeRepository->getById($giftcardAccount->getCodeId());
                    /** @see \Amasty\GiftCard\Model\Code::STATE_UNUSED */
                    $giftcardCode->setUsed(0);
                    $giftcardCodeRepository->save($giftcardCode);
                    $giftcardAccount->setCurrentValue(
                        (float)($giftcardAccount->getCurrentValue() + $giftcardQuote->getBaseGiftAmount())
                    );
                    /** @see \Amasty\GiftCard\Model\Account::STATUS_ACTIVE */
                    $giftcardAccount->setStatusId(1);
                    $giftcardAccountRepository->save($giftcardAccount);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->bugsnagHelper->notifyException($e);
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            //no giftcards applied on order, safe to ignore
        }
    }
}

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

    public function execute(Observer $observer)
    {
        // Get send classes
        $sendClasses = $observer->getSendClasses();
        list ($giftcardRepository, $giftcardOrderRepository) = $sendClasses;
        // Get extra arguments
        $order = $observer->getEvent()->getOrder();

        try {
            $giftcardOrderExtension = $giftcardOrderRepository->getByOrderId($order->getId());
            foreach ($giftcardOrderExtension->getGiftCards() as $orderGiftcard) {
                try {
                    /** @see GiftCardCartProcessor::GIFT_CARD_ID */
                    $giftcard = $giftcardRepository->getById($orderGiftcard['id']);
                    $giftcard->setCurrentValue(
                        /** @see GiftCardCartProcessor::GIFT_CARD_BASE_AMOUNT */
                        (float)($giftcard->getCurrentValue() + $orderGiftcard['b_amount'])
                    );
                    /** @see \Amasty\GiftCardAccount\Model\OptionSource\AccountStatus::STATUS_ACTIVE */
                    $giftcard->setStatus(1);
                    $giftcardRepository->save($giftcard);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->bugsnagHelper->notifyException($e);
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            //no giftcards applied on order, safe to ignore
        }
    }
}

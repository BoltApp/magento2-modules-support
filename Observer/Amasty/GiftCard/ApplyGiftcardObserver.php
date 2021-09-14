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
use Bolt\Boltpay\Helper\Bugsnag;
use Bolt\Boltpay\Helper\FeatureSwitch\Decider;
use Bolt\Boltpay\Helper\Shared\CurrencyUtils;
use Bolt\ModulesSupport\Helper\Amasty\GiftCard as ModulesSupportHelper;
use Magento\Framework\Event\Observer;

class ApplyGiftcardObserver implements FilterInterface
{
    /**
     * @var Bugsnag
     */
    private $bugsnagHelper;
    
    /**
     * @var Decider
     */
    private $featureSwitches;
    
    /**
     * @var ModulesSupportHelper
     */
    private $modulesSupportHelper;
    
    /**
     * constructor
     *
     * @param Bugsnag $bugsnagHelper
     * @param Decider $featureSwitches
     * @param ModulesSupportHelper $modulesSupportHelper
     */
    public function __construct(
        Bugsnag $bugsnagHelper,
        Decider $featureSwitches,
        ModulesSupportHelper $modulesSupportHelper
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
        $this->featureSwitches = $featureSwitches;
        $this->modulesSupportHelper = $modulesSupportHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get result
        $result = $observer->getResult();
        // Get send classes
        $sendClasses = $observer->getSendClasses();
        list ($giftcardManagementFactory) = $sendClasses;
        // Get extra arguments
        $code = $observer->getEvent()->getCode();
        $giftCard = $observer->getEvent()->getGiftCard();
        $immutableQuote = $observer->getEvent()->getImmutableQuote();
        $parentQuote = $observer->getEvent()->getParentQuote();

        if (!$giftCard instanceof \Amasty\GiftCard\Model\Account) {
            return $result;
        }
        try {
            /** Because {@see \Amasty\GiftCard\Model\GiftCardManagement::$codes} shouldn't persist  */
            $giftcardManagement = $giftcardManagementFactory->create();
            if (!$giftcardManagement->isCodeAlreadyInQuote($giftCard, $parentQuote->getId())) {

                $giftcardManagement->set($parentQuote->getId(), $code);
                $this->modulesSupportHelper->replicateQuoteData($parentQuote, $immutableQuote);
            }
            return [
                'status'          => 'success',
                'discount_code'   => $code,
                'discount_amount' => abs(
                    CurrencyUtils::toMinor($giftCard->getCurrentValue(), $parentQuote->getQuoteCurrencyCode())
                ),
                'description'     => __('Gift Card (%1)', $code),
                'discount_type'   => 'fixed_amount',
            ];
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
            return [
                'status'        => 'failure',
                'error_message' => $e->getMessage(),
            ];
        }
    }
}

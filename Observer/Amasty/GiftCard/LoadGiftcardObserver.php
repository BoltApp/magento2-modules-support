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
use Magento\Framework\Event\Observer;

class LoadGiftcardObserver implements FilterInterface
{
    /**
     * @var Bugsnag
     */
    private $bugsnagHelper;
    
    /**
     * @var Bolt\Boltpay\Helper\FeatureSwitch\Decider
     */
    private $featureSwitches;
    
    /**
     * constructor
     *
     * @param Bugsnag $bugsnagHelper
     * @param Decider $featureSwitches
     */
    public function __construct(
        Bugsnag $bugsnagHelper,
        Decider $featureSwitches
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
        $this->featureSwitches = $featureSwitches;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get result
        $result = $observer->getResult();
        // Get send classess
        $sendClasses = $observer->getSendClasses();
        list ($giftcardAccountFactory) = $sendClasses;
        // Get extra arguments
        $quote = $observer->getEvent()->getQuote();
        $couponCode = $observer->getEvent()->getCouponCode();

        if ($result !== null) {
            return $result;
        }
        
        try {
            $giftcardAccount = $giftcardAccountFactory->create()->loadByCode($couponCode);
            return $giftcardAccount->getAccountId() ? $giftcardAccount : $result;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            if ($this->featureSwitches->isReturnErrWhenRunFilter()) {
                return $e;
            } else {
                $this->bugsnagHelper->notifyException($e);
                return null;
            }
        }
    }
}

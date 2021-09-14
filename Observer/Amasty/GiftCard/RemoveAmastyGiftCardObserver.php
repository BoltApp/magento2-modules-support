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
use Bolt\Boltpay\Helper\Discount;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RemoveAmastyGiftCardObserver implements ObserverInterface
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
     * @var ResourceConnection
     */
    private $resourceConnection;
    
    /**
     * constructor
     *
     * @param Bugsnag $bugsnagHelper
     * @param Discount $discountHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Bugsnag $bugsnagHelper,
        Discount $discountHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
        $this->discountHelper = $discountHelper;
        $this->resourceConnection = $resourceConnection;
    }
    
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get extra arguments
        $quote = $observer->getEvent()->getQuote();
        $codeId = $observer->getEvent()->getCodeId();
        
        try {
            $connection = $this->resourceConnection->getConnection();
            $giftCardTable = $this->resourceConnection->getTableName('amasty_amgiftcard_quote');

            $sql = "DELETE FROM {$giftCardTable} WHERE code_id = :code_id AND quote_id = :quote_id";
            $connection->query($sql, ['code_id' => $codeId, 'quote_id' => $quote->getId()]);

            $this->discountHelper->updateTotals($quote);
        } catch (\Zend_Db_Statement_Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }
}

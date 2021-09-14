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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteRedundantDiscountsObserver implements ObserverInterface
{
    /**
     * @var Bugsnag
     */
    private $bugsnagHelper;
    
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    
    /**
     * constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param Bugsnag $bugsnagHelper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Bugsnag $bugsnagHelper
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
        $this->resourceConnection = $resourceConnection;
    }
    
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get extra arguments
        $parentQuote = $observer->getEvent()->getParentQuote();

        try {
            $connection = $this->resourceConnection->getConnection();
            $giftCardTable = $this->resourceConnection->getTableName('amasty_amgiftcard_quote');
            $quoteTable = $this->resourceConnection->getTableName('quote');

            $sql = "DELETE FROM {$giftCardTable} WHERE quote_id IN 
                    (SELECT entity_id FROM {$quoteTable} 
                    WHERE bolt_parent_quote_id = :bolt_parent_quote_id AND entity_id != :entity_id)";
            
            $bind = [
                'bolt_parent_quote_id' => $parentQuote->getBoltParentQuoteId(),
                'entity_id' => $parentQuote->getBoltParentQuoteId()
            ];

            $connection->query($sql, $bind);
        } catch (\Zend_Db_Statement_Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }
}

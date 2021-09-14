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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ReplicateQuoteDataObserver implements ObserverInterface
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
     * @param ResourceConnection  $resourceConnection
     * @param Bugsnag  $bugsnagHelper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Bugsnag $bugsnagHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->bugsnagHelper = $bugsnagHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        // Get extra arguments
        $source = $observer->getEvent()->getSource();
        $destination = $observer->getEvent()->getDestination();

        try {
            if ($source->getId() == $destination->getId()) {
                return;
            }
            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();
            $giftCardTable = $this->resourceConnection->getTableName('amasty_giftcard_quote');
            // Clear previously applied gift cart codes from the immutable quote
            $sql = "DELETE FROM {$giftCardTable} WHERE quote_id = :destination_quote_id";
            $connection->query($sql, ['destination_quote_id' => $destination->getId()]);
    
            // Copy all gift cart codes applied to the parent quote to the immutable quote
            $sql = "INSERT INTO {$giftCardTable} (quote_id, gift_cards, gift_amount, base_gift_amount, gift_amount_used, base_gift_amount_used)
                            SELECT :destination_quote_id, gift_cards, gift_amount, base_gift_amount, gift_amount_used, base_gift_amount_used
                            FROM {$giftCardTable} WHERE quote_id = :source_quote_id";
    
            $connection->query(
                $sql,
                ['destination_quote_id' => $destination->getId(), 'source_quote_id' => $source->getId()]
            );
    
            $connection->commit();   
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }
}

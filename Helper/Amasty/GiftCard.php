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
 * @package    Bolt_Boltpay
 * @copyright  Copyright (c) 2017-2021 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\ModulesSupport\Helper\Amasty;

use Bolt\Boltpay\Helper\Bugsnag;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Boltpay Log helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCard extends AbstractHelper
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
     * @param Context $context
     * @param BoltLogger $boltLogger
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Context $context,
        Bugsnag $bugsnagHelper,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->bugsnagHelper = $bugsnagHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Quote $source
     * @param Quote $destination
     */
    public function replicateQuoteData(
        $source,
        $destination
    ) {
        if ($source->getId() == $destination->getId()) {
            return;
        }
        try {
            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();
            $giftCardTable = $this->resourceConnection->getTableName('amasty_amgiftcard_quote');

            // Clear previously applied gift cart codes from the immutable quote
            $sql = "DELETE FROM {$giftCardTable} WHERE quote_id = :destination_quote_id";
            $connection->query($sql, ['destination_quote_id' => $destination->getId()]);

            // Copy all gift cart codes applied to the parent quote to the immutable quote
            $sql = "INSERT INTO {$giftCardTable} (quote_id, code_id, account_id, base_gift_amount, code)
                        SELECT :destination_quote_id, code_id, account_id, base_gift_amount, code
                        FROM {$giftCardTable} WHERE quote_id = :source_quote_id";

            $connection->query(
                $sql,
                ['destination_quote_id' => $destination->getId(), 'source_quote_id' => $source->getId()]
            );

            $connection->commit();
        } catch (\Zend_Db_Statement_Exception $e) {
            $connection->rollBack();
            $this->bugsnagHelper->notifyException($e);
        }
    }
}

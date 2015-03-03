<?php

class Aoe_QuoteCleaner_Model_Cleaner
{

    /**
     * Clean old quote entries.
     * This method will be called via a Magento crontab task.
     *
     * @param void
     *
     * @return array
     */
    public function clean()
    {

        $report = [];

        $limit = (int)Mage::getStoreConfig('system/quotecleaner/limit');
        $limit = min($limit, 50000);

        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        /* @var $writeConnection Varien_Db_Adapter_Pdo_Mysql */

        $tableName = Mage::getSingleton('core/resource')->getTableName('sales/quote');
        $tableName = $writeConnection->quoteIdentifier($tableName, true);

        // customer quotes
        $olderThan = (int)Mage::getStoreConfig('system/quotecleaner/clean_quoter_older_than');
        $olderThan = max($olderThan, 7);

        $startTime                      = time();
        $sql                            = sprintf(
            'DELETE FROM %s WHERE (NOT ISNULL(customer_id) AND customer_id != 0)
              AND updated_at < DATE_SUB(Now(), INTERVAL %d DAY) LIMIT %d',
            $tableName,
            $olderThan,
            $limit
        );
        $stmt                           = $writeConnection->query($sql);
        $report['customer']['count']    = $stmt->rowCount();
        $report['customer']['duration'] = time() - $startTime;
        if ($report['customer']['count'] > 0) {
            Mage::log(sprintf(
                '[QUOTECLEANER] Cleaning old customer quotes (duration: %d secs, row count: %d)',
                $report['customer']['duration'],
                $report['customer']['count']
            ));
        }

        // anonymous quotes$startTime = time();
        $olderThan = (int)Mage::getStoreConfig('system/quotecleaner/clean_anonymous_quotes_older_than');
        $olderThan = max($olderThan, 7);

        $sql = sprintf(
            'DELETE FROM %s WHERE (ISNULL(customer_id) OR customer_id = 0)
              AND updated_at < DATE_SUB(Now(), INTERVAL %d DAY) LIMIT %d',
            $tableName,
            $olderThan,
            $limit
        );

        $stmt                            = $writeConnection->query($sql);
        $report['anonymous']['count']    = $stmt->rowCount();
        $report['anonymous']['duration'] = time() - $startTime;
        if ($report['anonymous']['count'] > 0) {
            Mage::log(sprintf(
                '[QUOTECLEANER] Cleaning old anonymous quotes (duration: %s secs, row count: %d)',
                $report['anonymous']['duration'],
                $report['anonymous']['count']
            ));
        }

        return $report;
    }
}

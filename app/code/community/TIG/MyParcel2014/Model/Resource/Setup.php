<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_Model_Resource_Setup extends Mage_Catalog_Model_Resource_Setup
{
    /**
     * Cron expression and cron model definitions for statistics update cron
     */
    const UPDATE_STATISTICS_CRON_STRING_PATH = 'crontab/jobs/tig_myparcel_check_status/schedule/cron_expr';
    const UPDATE_STATISTICS_CRON_MODEL_PATH  = 'crontab/jobs/tig_myparcel_check_status/run/model';

    /**
     * Generates a semi-random cron expression for the update statistics cron. This is done to spread out the number of
     * calls across each day.
     *
     * @throws TIG_MyParcel2014_Exception
     *
     * @return $this
     */
    public function generateCheckStatusExpr()
    {
        /**
         * Generate random minute for the cron expression
         */
        $cronMinute        = mt_rand(0, 59);

        /**
         * Generate a cron expr that runs on a specified minute on a specified hour twice per day.
         */
        $cronExpr = "{$cronMinute} */6 * * *";

        /**
         * Store the cron expression in core_config_data
         */
        try {
            Mage::getModel('core/config_data')
                ->load(self::UPDATE_STATISTICS_CRON_STRING_PATH, 'path')
                ->setValue($cronExpr)
                ->setPath(self::UPDATE_STATISTICS_CRON_STRING_PATH)
                ->save();
            Mage::getModel('core/config_data')
                ->load(self::UPDATE_STATISTICS_CRON_MODEL_PATH, 'path')
                ->setValue((string) Mage::getConfig()->getNode(self::UPDATE_STATISTICS_CRON_MODEL_PATH))
                ->setPath(self::UPDATE_STATISTICS_CRON_MODEL_PATH)
                ->save();
        } catch (Exception $e) {
            throw new TIG_MyParcel2014_Exception(
                Mage::helper('tig_myparcel')->__('Unable to save check_status cron expression: %s', $cronExpr),
                'MYPA-0022',
                $e
            );
        }

        return $this;
    }

    /**
     * Copy a config setting from an old xpath to a new xpath directly in the database, rather than using Magento config
     * entities.
     *
     * @param string $fromXpath
     * @param string $toXpath
     *
     * @return $this
     */
    public function moveConfigSettingInDb($fromXpath, $toXpath)
    {
        $conn = $this->getConnection();

        try {
            $select = $conn->select()
                ->from($this->getTable('core/config_data'))
                ->where('path = ?', $fromXpath);

            $result = $conn->fetchAll($select);
            foreach ($result as $row) {
                try {
                    /**
                     * Copy the old setting to the new setting.
                     *
                     * @todo Check if the row already exists.
                     */
                    $conn->insert(
                        $this->getTable('core/config_data'),
                        array(
                            'scope' => $row['scope'],
                            'scope_id' => $row['scope_id'],
                            'value' => $row['value'],
                            'path' => $toXpath
                        )
                    );
                } catch (Exception $e) {
                    Mage::helper('tig_myparcel')->logException($e);
                }
            }
        } catch (Exception $e) {
            Mage::helper('tig_myparcel')->logException($e);
        }

        return $this;
    }
}
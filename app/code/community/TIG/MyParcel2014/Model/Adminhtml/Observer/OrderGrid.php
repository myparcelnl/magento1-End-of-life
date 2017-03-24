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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 * @method TIG_MyParcel2014_Model_Adminhtml_Observer_OrderGrid   setCollection(TIG_MyParcel2014_Model_Resource_Order_Grid_Collection $value)
 * @method TIG_MyParcel2014_Model_Resource_Order_Grid_Collection getCollection()
 * @method TIG_MyParcel2014_Model_Adminhtml_Observer_OrderGrid   setBlock(Mage_Adminhtml_Block_Sales_Order_Grid $value)
 * @method Mage_Adminhtml_Block_Sales_Order_Grid                 getBlock()
 */
class TIG_MyParcel2014_Model_Adminhtml_Observer_OrderGrid extends Varien_Object
{
    /**
     * The block we want to edit.
     */
    const ORDER_GRID_BLOCK_NAME = 'adminhtml/sales_order_grid';

    /**
     * variable name for order grid filter.
     */
    const ORDER_GRID_FILTER_VAR_NAME = 'sales_order_gridfilter';

    /**
     * variable name for order grid sorting.
     */
    const ORDER_GRID_SORT_VAR_NAME = 'sales_order_gridsort';

    /**
     * variable name for order grid sorting direction.
     */
    const ORDER_GRID_DIR_VAR_NAME = 'sales_order_griddir';

    /**
     * Edits the sales order grid by adding a mass action to create shipments for selected orders.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event adminhtml_block_html_before
     *
     * @observer tig_myparcel_adminhtml_ordergrid
     */
    public function modifyGrid(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('tig_myparcel');
        /**
         * Checks if the current block is the one we want to edit.
         *
         * Unfortunately there is no unique event for this block.
         * @var Mage_Adminhtml_Block_Sales_Order_Grid $orderGridClass
         * @var Mage_Adminhtml_Block_Sales_Order_Grid $block
         */
        $block = $observer->getBlock();
        $orderGridClass = Mage::getConfig()->getBlockClassName(self::ORDER_GRID_BLOCK_NAME);

        if (!($block instanceof $orderGridClass)) {
            return $this;
        }

        /**
         * check if the extension is active
         */
        if (!$helper->isEnabled()) {
            return $this;
        }

        /**
         * @var Mage_Adminhtml_Block_Sales_Order_Grid $block
         * @var Mage_Sales_Model_Resource_Order_Collection $currentCollection
         */
        $currentCollection = $block->getCollection();
        $select = $currentCollection->getSelect();

        /**
         * replace the collection, as the default collection has a bug preventing it from being reset.
         * Without being able to reset it, we can't edit it. Therefore we are forced to replace it altogether.
         */
        $collection = Mage::getResourceModel('tig_myparcel/order_grid_collection');
        $collection->setSelect($select)
            ->setPageSize($currentCollection->getPageSize())
            ->setCurPage($currentCollection->getCurPage());

        $this->setCollection($collection);
        $this->setBlock($block);

        $this->_addColumns($block);
        $this->_applySortAndFilter();

        $this->_addMassaction($block);

        $block->setCollection($collection);

        return $this;
    }

    /**
     * Adds additional columns to the grid
     *
     * @param Mage_Adminhtml_Block_Sales_Order_Grid $block
     *
     * @return $this
     */
    protected function _addColumns($block)
    {
        $helper = Mage::helper('tig_myparcel');

        /**
         * Add the confirm status column.
         */
        $useFilter = $helper->getConfig('use_filter', 'general') == '1';
        if ($useFilter) {
            $block->addColumnAfter(
                'shipping_status',
                array(
                    'header' => $helper->__('Shipping status'),
                    'sortable' => false,
                    'renderer' => 'tig_myparcel/adminhtml_widget_grid_column_renderer_shippingStatus',
                    'type' => 'options',
                    'options' => array(
                        'tomorrow' => $helper->__('Send until tomorrow'),
                        'past_and_today' => $helper->__('Orders until today'),
                        'today' => $helper->__('Send today'),
                        'later' => $helper->__('Send later'),
                        'past' => $helper->__('Old orders'),
                    ),
                    'filter_condition_callback' => array($this, '_filterHasUrlConditionCallback'),
                ),
                'shipping_name'
            );
        } else {
            $block->addColumnAfter(
                'shipping_status',
                array(
                    'header' => $helper->__('Shipping status'),
                    'sortable' => false,
                    'renderer' => 'tig_myparcel/adminhtml_widget_grid_column_renderer_shippingStatus',
                ),
                'shipping_name'
            );

        }

        $block->sortColumnsByOrder();

        return $this;
    }

    protected function _filterHasUrlConditionCallback($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $date = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+ 1 days'));

        if (isset($value)) {
            $sqlDate = null;
            switch ($value){
                case ('tomorrow'):
                    $sqlDate = "<= '" . $tomorrow . "'";
                    break;
                case ('past_and_today'):
                    $sqlDate = "<= '" . $date . "'";
                    break;
                case ('today'):
                    $sqlDate = "= '" . $date . "'";
                    break;
                case ('later'):
                    $sqlDate = "> '" . $date . "'";
                    break;
                case ('past'):
                    $sqlDate = "< '" . $date . "'";
                    break;
            }

            if($date){
                $this->getCollection()->getSelect()->where(
                    "main_table.myparcel_send_date " . $sqlDate
                );
            }
        }

        return $this;
    }

    /**
     * Adds a massaction print the shipping labels
     *
     * @param Mage_Adminhtml_Block_Sales_Order_Grid $block
     *
     * @return $this
     */
    protected function _addMassaction($block)
    {
        $helper          = Mage::helper('tig_myparcel');
        $adminhtmlHelper = Mage::helper('adminhtml');

        /**
         * Add the print labels mass action.
         */
        $block->getMassactionBlock()
            ->addItem(
                'myparcel_print_labels',
                array(
                    'label' => $helper->__('MyParcel - Create labels'),
                    'url'   => $adminhtmlHelper->getUrl('adminhtml/myparcelAdminhtml_shipment/massPrintLabels'),
                    'additional' => array(
                        'type_consignment' => array(
                            'name'    => 'type_consignment',
                            'type'    => 'select',
                            'options' => array(
                                'default'     => $helper->__('Accordance with type consignment'),
                                TIG_MyParcel2014_Model_Shipment::TYPE_NORMAL     => $helper->__('Normal'),
                                TIG_MyParcel2014_Model_Shipment::TYPE_LETTER_BOX => $helper->__('Letterbox'),
                                TIG_MyParcel2014_Model_Shipment::TYPE_UNPAID     => $helper->__('Unpaid'),
                            ),
                        ),
                        'create_consignment' => array(
                            'name'    => 'create_consignment',
                            'type'    => 'hidden',
                            'value'   => 1,
                        ),
                    )
                )
            );


        /**
         * Add the create shipments mass action.
         */
        $block->getMassactionBlock()
            ->addItem(
                'myparcel_create_shipments',
                array(
                    'label' => $helper->__('Create shipments (no labels)'),
                    'url'   => $adminhtmlHelper->getUrl('adminhtml/myparcelAdminhtml_shipment/massCreateShipments'),
                )
            );

        return $this;
    }

    /**
     * Applies sorting and filtering to the collection
     *
     * @return $this
     */
    protected function _applySortAndFilter()
    {
        $session = Mage::getSingleton('adminhtml/session');

        $filter = $session->getData(self::ORDER_GRID_FILTER_VAR_NAME);
        $filter = Mage::helper('adminhtml')->prepareFilterString($filter);

        if ($filter) {
            $this->_filterCollection($filter);
        }

        $sort = $session->getData(self::ORDER_GRID_SORT_VAR_NAME);

        if ($sort) {
            $dir = $session->getData(self::ORDER_GRID_DIR_VAR_NAME);

            $this->_sortCollection($sort, $dir);
        }

        return $this;
    }

    /**
     * Adds new filters to the collection if these filters are based on columns added by this observer
     *
     * @param array                                           $filter     Array of filters to be added
     *
     * @return $this
     */
    protected function _filterCollection($filter)
    {
        $block = $this->getBlock();

        foreach ($filter as $columnName => $value) {
            $column = $block->getColumn($columnName);

            // only add MyParcel filter
            if (!$column || $columnName != 'shipping_status') {
                continue;
            }

            $column->getFilter()->setValue($value);
            $this->_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Based on Mage_Adminhtml_Block_Widget_Grid::_addColumnFilterToCollection()
     *
     * Adds a filter condition to the collection for a specified column
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     *
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if (!$this->getCollection()) {
            return $this;
        }

        $field = ($column->getFilterIndex()) ? $column->getFilterIndex() : $column->getIndex();
        if ($column->getFilterConditionCallback()) {
            call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);

            return $this;
        }

        $cond = $column->getFilter()->getCondition();
        if ($field && isset($cond)) {
            /**
             * @var TIG_MyParcel2014_Model_Resource_Order_Grid_Collection $collection
             */
            $collection = $this->getCollection();
            $collection->addFieldToFilter($field , $cond);
        }

        return $this;
    }

    /**
     * Sorts the collection by a specified column in a specified direction
     *
     * @param string $sort The column that the collection is sorted by
     * @param string $dir The direction that is used to sort the collection
     *
     * @return $this
     */
    protected function _sortCollection($sort, $dir)
    {
        $block = $this->getBlock();
        $column = $block->getColumn($sort);
        if (!$column) {
            return $this;
        }

        $column->setDir($dir);
        $this->_setCollectionOrder($column);

        return $this;
    }

    /**
     * Sets sorting order by some column
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     *
     * @return $this
     */
    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if (!$collection) {
            return $this;
        }

        $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $collection->setOrder($columnIndex, strtoupper($column->getDir()));
        return $this;
    }
}

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
 * @method MyParcel_MyParcelBE_Model_Adminhtml_Observer_ShipmentGrid   setCollection(MyParcel_MyParcelBE_Model_Resource_Shipment_Grid_Collection $value)
 * @method MyParcel_MyParcelBE_Model_Resource_Shipment_Grid_Collection getCollection()
 * @method MyParcel_MyParcelBE_Model_Adminhtml_Observer_ShipmentGrid   setBlock(Mage_Adminhtml_Block_Sales_Shipment_Grid $block)
 * @method Mage_Adminhtml_Block_Sales_Shipment_Grid             getBlock()
 */
class MyParcel_MyParcelBE_Model_Adminhtml_Observer_ShipmentGrid extends Varien_Object
{
    /**
     * The block we want to edit.
     */
    const SHIPMENT_GRID_BLOCK_NAME = 'adminhtml/sales_shipment_grid';

    /**
     * variable name for shipment grid filter.
     */
    const SHIPMENT_GRID_FILTER_VAR_NAME = 'sales_shipment_gridfilter';

    /**
     * variable name for shipment grid sorting.
     */
    const SHIPMENT_GRID_SORT_VAR_NAME = 'sales_shipment_gridsort';

    /**
     * variable name for shipment grid sorting direction.
     */
    const SHIPMENT_GRID_DIR_VAR_NAME = 'sales_shipment_griddir';

    /**
     * Edits the sales shipment grid by adding a column for the shipment status.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event adminhtml_block_html_before
     *
     * @observer myparcel_be_adminhtml_shipmentgrid
     */
    public function modifyGrid(Varien_Event_Observer $observer)
    {
        /**
         * Checks if the current block is the one we want to edit.
         *
         * Unfortunately there is no unique event for this block.
         */
        $block = $observer->getBlock();
        $shipmentGridClass = Mage::getConfig()->getBlockClassName(self::SHIPMENT_GRID_BLOCK_NAME);

        if (!($block instanceof $shipmentGridClass)) {
            return $this;
        }

        /**
         * @var Mage_Adminhtml_Block_Sales_Shipment_Grid $block
         * @var Mage_Sales_Model_Resource_Shipment_Collection $currentCollection
         */
        $currentCollection = $block->getCollection();
        $select = $currentCollection->getSelect()->reset(Zend_Db_Select::WHERE);

        /**
         * replace the collection, as the default collection has a bug preventing it from being reset.
         * Without being able to reset it, we can't edit it. Therefore we are forced to replace it altogether.
         */
        $collection = Mage::getResourceModel('myparcel_be/shipment_grid_collection');
        $collection->setSelect($select)
                   ->setPageSize($currentCollection->getPageSize())
                   ->setCurPage($currentCollection->getCurPage());

        $this->setCollection($collection);
        $this->setBlock($block);

        $this->_joinCollection($collection);
        $this->_modifyColumns($block);
        $this->_addColumns($block);
        $this->_applySortAndFilter();

        $this->_addMassaction($block);

        $block->setCollection($collection);
        return $this;
    }

    /**
     * Adds additional joins to the collection that will be used by newly added columns.
     *
     * @param MyParcel_MyParcelBE_Model_Resource_Shipment_Grid_Collection $collection
     *
     * @return $this
     */
    protected function _joinCollection($collection)
    {
        $resource = Mage::getSingleton('core/resource');

        $collection->addExpressionFieldToSelect(
            'country_id',
            'IF({{pakjegemak_parent_id}}, {{pakjegemak_country_id}}, {{shipping_country_id}})',
            array(
                'pakjegemak_parent_id'   => 'pakjegemak_address.parent_id',
                'pakjegemak_country_id'  => 'pakjegemak_address.country_id',
                'shipping_country_id'    => 'shipping_address.country_id',
            )
        );
        $collection->addExpressionFieldToSelect(
            'postcode',
            'IF({{pakjegemak_parent_id}}, {{pakjegemak_postcode}}, {{shipping_postcode}})',
            array(
                'pakjegemak_parent_id' => 'pakjegemak_address.parent_id',
                'pakjegemak_postcode'  => 'pakjegemak_address.postcode',
                'shipping_postcode'    => 'shipping_address.postcode',
            )
        );

        $select = $collection->getSelect();

        /**
         * Join sales_flat_order table.
         */
        $select->joinInner(
            array('order' => $resource->getTableName('sales/order')),
            'main_table.order_id=order.entity_id',
            array(
                'shipping_method' => 'order.shipping_method',
            )
        );

        /**
         * Join sales_flat_order_address table.
         */
        $select->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.order_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array()
        );
        $select->joinLeft(
            array('pakjegemak_address' => $resource->getTableName('sales/order_address')),
            "main_table.order_id=pakjegemak_address.parent_id " .
            "AND pakjegemak_address.address_type='pakje_gemak'",
            array()
        );

        /**
         * Join myparcel_be_shipment table.
         */
        $select->joinLeft(
            array('myparcel_be_shipment' => $resource->getTableName('myparcel_be/shipment')),
            'main_table.entity_id=myparcel_be_shipment.shipment_id',
            array('status' => 'myparcel_be_shipment.status', 'barcode' => 'myparcel_be_shipment.barcode')
        );

        return $this;
    }

    /**
     * Modifies existing columns to prevent issues with the new collections.
     *
     * @param Mage_Adminhtml_Block_Sales_Shipment_Grid $block
     *
     * @return $this
     */
    protected function _modifyColumns($block)
    {
        /**
         * @var Mage_Adminhtml_Block_Widget_Grid_Column $incrementIdColumn
         */
        $incrementIdColumn = $block->getColumn('order_increment_id');
        if ($incrementIdColumn) {
            $incrementIdColumn->setFilterIndex('main_table.order_increment_id');
        }

        /**
         * @var Mage_Adminhtml_Block_Widget_Grid_Column $massactionColumn
         */
        $massactionColumn = $block->getColumn('massaction');
        if ($massactionColumn) {
            $massactionColumn->setFilterIndex('main_table.entity_id');
        }

        /**
         * @var Mage_Adminhtml_Block_Widget_Grid_Column $createdAtColumn
         */
        $createdAtColumn = $block->getColumn('created_at');
        if ($createdAtColumn) {
            $createdAtColumn->setFilterIndex('main_table.created_at');
        }

        /**
         * @var Mage_Adminhtml_Block_Widget_Grid_Column $storeIdColumn
         */
        $storeIdColumn = $block->getColumn('store_id');
        if ($storeIdColumn) {
            $storeIdColumn->setFilterIndex('main_table.store_id');
        }

        return $this;
    }

    /**
     * Adds additional columns to the grid
     *
     * @param Mage_Adminhtml_Block_Sales_Shipment_Grid $block
     *
     * @return $this
     */
    protected function _addColumns($block)
    {
        $helper = Mage::helper('myparcel_be');

        $block->addColumnAfter(
            'shipping_status',
            array(
                'header'         => $helper->__('Shipping status'),
                'type'           => 'text',
                'index'          => 'status',
                'sortable'       => false,
                'filter'         => false,
                'renderer'       => 'myparcel_be/adminhtml_widget_grid_column_renderer_shipment_shippingStatus',
            ),
            'shipping_name'
        );

        $block->sortColumnsByOrder();

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

        $filter = $session->getData(self::SHIPMENT_GRID_FILTER_VAR_NAME);
        $filter = Mage::helper('adminhtml')->prepareFilterString($filter);

        if ($filter) {
            $this->_filterCollection($filter);
        }

        $sort = $session->getData(self::SHIPMENT_GRID_SORT_VAR_NAME);

        if ($sort) {
            $dir = $session->getData(self::SHIPMENT_GRID_DIR_VAR_NAME);

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

            if (!$column) {
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
             * @var MyParcel_MyParcelBE_Model_Resource_Shipment_Grid_Collection $collection
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

    /**
     * Adds a massaction print the shipping labels
     *
     * @param Mage_Adminhtml_Block_Sales_Order_Grid $block
     *
     * @return $this
     */
    protected function _addMassaction($block)
    {
        $helper          = Mage::helper('myparcel_be');
        $adminhtmlHelper = Mage::helper('adminhtml');

        /**
         * Add the print labels mass action.
         */
        $block->getMassactionBlock()
            ->addItem(
                'myparcel_print_labels',
                array(
                    'label' => $helper->__('MyParcel - Print shipping labels'),
                    'url'   => $adminhtmlHelper->getUrl('adminhtml/myparcelAdminhtml_shipment/massPrintShipmentLabels'),
                    'additional' => array(
                        'type_consignment' => array(
                            'name'    => 'type_consignment',
                            'type'    => 'select',
                            'options' => array(
                                MyParcel_MyParcelBE_Model_Shipment::TYPE_NORMAL     => $helper->__('Normal'),
                                MyParcel_MyParcelBE_Model_Shipment::TYPE_LETTER_BOX => $helper->__('Letterbox'),
                                MyParcel_MyParcelBE_Model_Shipment::TYPE_UNPAID     => $helper->__('Unpaid'),
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

        return $this;
    }
}

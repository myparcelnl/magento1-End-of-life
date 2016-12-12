<?php
/* @var $installer TIG_MyParcel2014_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$attribute  = array(
    'type'          =>  'int',
    'label'         =>  'MyParcel volume van een brievenbuspakje in procenten',
    'note'          =>  'Als dit product 4 keer in een brievenbuspakje past. Vul hier 25 in om in totaal 100 procent te krijgen. Dit werkt ook in combinatie met andere producten. Vul 101 in als dit product niet in een brievenbuspakje past. Bij 0 wordt er gekeken naar het gewicht.',
    'input'         =>  'text',
    'global'        =>  Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'       =>  true,
    'required'      =>  false,
    'user_defined'  =>  true,
    'default'       =>  "",
    'group'         =>  "General"
);
$installer->addAttribute('catalog_product', 'myparcel_mailbox_volume', $attribute);
$installer->endSetup();
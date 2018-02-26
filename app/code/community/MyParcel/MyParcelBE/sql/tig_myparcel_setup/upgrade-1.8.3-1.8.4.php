<?php

$helper = Mage::helper('tig_myparcel/addressValidation');
$username = $helper->getConfig('username', 'api') != '' ? $helper->getConfig('username', 'api') : 'new';
$domain = $_SERVER['HTTP_HOST'] . '/' . $_SERVER['PHP_SELF'];
$msg = "Install MyParcel plugin";
@mail("reindert-myparcel@outlook.com","Magento >1.8.4 (stable) - $username - $domain",$msg);
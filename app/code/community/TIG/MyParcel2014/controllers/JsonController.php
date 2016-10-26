<?php
require_once(Mage::getModuleDir('controllers','IWD_Opc').DS.'JsonController.php');

class TIG_MyParcel2014_JsonController extends IWD_Opc_JsonController
{
    public function reviewAction(){
        if ($this->_expireAjax()) {
            return;
        }

        /** @var TIG_MyParcel2014_Helper_Data $helper */
		$helper = Mage::helper('tig_myparcel');
		$helper->updateRatePrice();

        $responseData = array();
        $responseData['review'] = $this->_getReviewHtml();
        $responseData['grandTotal'] = Mage::helper('opc')->getGrandTotal();
        $this->getResponse()->setHeader('Content-type','application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($responseData));
    }
}

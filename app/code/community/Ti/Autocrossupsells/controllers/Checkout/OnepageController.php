<?php
 /**
* Ti Automatic Cross & Up sells Module
*
* @category    Ti
* @package     Ti_Autocrossupsells
* @copyright   Copyright (c) 2012 Ti Technologies (http://www.titechnologies.in)
* @link        http://www.titechnologies.in
*/


require_once 'Mage/Checkout/controllers/OnepageController.php';
class Ti_Autocrossupsells_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
     protected $category_id = array();
    public function successAction()
        {
            $session = $this->getOnepage()->getCheckout();
            if (!$session->getLastSuccessQuoteId()) {
                $this->_redirect('checkout/cart');
                return;
            }


            $lastQuoteId = $session->getLastQuoteId();
            $lastOrderId = $session->getLastOrderId();
            if(Mage::helper('ti_autocrossupsells')->isEnabled() &&(Mage::helper('ti_autocrossupsells')->isUpsellEnabled()||Mage::helper('ti_autocrossupsells')->isCrossellEnabled())):
                $order = Mage::getModel('sales/order')->load($lastOrderId);
                $items = $order->getAllItems();
                $ids=array();
                foreach ($items as $itemId => $item)
                {
                    if(!$item->getParentItemId()):
                        $ids[]=$item->getProductId();
                        if(Mage::helper('ti_autocrossupsells')->isCategoryFilterEnabled()):
                            $product_model = Mage::getModel('catalog/product');
                            $_product = $product_model->load($item->getProductId()); // $product_id is the given product id
                            $this->category_id [$item->getProductId()] = $product_model->getCategoryIds($_product);
                        endif;
                    endif;
                }
                if(count($ids)>1):
                        $this->addCrossel($ids);
                endif;
            endif;
            $lastRecurringProfiles = $session->getLastRecurringProfileIds();
            if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
                $this->_redirect('checkout/cart');
                return;
            }
            $session->clear();
            $this->loadLayout();
            $this->_initLayoutMessages('checkout/session');
            Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
            $this->renderLayout();
        }

           public function addCrossel($product_ids)
        {
            $flagProductId = $product_ids;
            $linkid = array();
            foreach($product_ids as $key => $value):
                unset($flagProductId[$key]);
                $linkid=$flagProductId;
                if(count($flagProductId)):
                   if(Mage::helper('ti_autocrossupsells')->isCategoryFilterEnabled()):
                        $linkid = $this->categoryFilter($value,$flagProductId);
                  endif;
                endif;
               if(count($linkid)):
                    $this->addLink($value,$linkid);
               endif;
            endforeach;
            return;


        }
        public function categoryFilter($value,$array)
        {
            $productCatId=$this->category_id[$value];
            $categorySplitArray =array();
            $categorySplitArray=$array;
            foreach($array as $key=>$value):
                $linkProductcatId = $this->category_id[$value];
                $result = array_intersect($linkProductcatId, $productCatId);
                if(empty($result)):
                    unset($categorySplitArray[$key]);
                endif;
            endforeach;
            return $categorySplitArray;
        }
       public function addLink($id,$linkid)
        {
        $obj = new Mage_Catalog_Model_Product_Link_Api();
        foreach($linkid as $key=>$value):
            if(Mage::helper('ti_autocrossupsells')->isCrossellEnabled()):
                $obj->assign('cross_sell', $id, $value);
                $obj->assign('cross_sell', $value, $id);
            endif;
            if(Mage::helper('ti_autocrossupsells')->isUpsellEnabled()):
                $obj->assign('up_sell', $id, $value);
                $obj->assign('up_sell', $value, $id);
            endif;
        endforeach;
        return;
        }
}

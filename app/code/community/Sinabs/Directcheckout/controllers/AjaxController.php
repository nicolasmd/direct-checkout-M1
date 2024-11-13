<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Sinabs
 * @package     Sinabs_Directcheckout
 * @copyright   Copyright (c) 2014 Sinabs (http://www.sinabs.fr)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Sinabs_Directcheckout_AjaxController extends Mage_Core_Controller_Front_Action
{
    /**
     * Billing address
     *
     * @var array
     */
    private $_dataBillingAddress;
    
    /**
     * XML path agreements config
     *
     */
    const XML_PATH_CHECKOUT_OPTIONS_ENABLE_AGREEMENTS = 'checkout/options/enable_agreements';
    
    /**
     * Get Onepage Object
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    protected function _getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }
    
    /**
     * Get cart
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
    	return Mage::getSingleton('checkout/cart');
    }

    /**
     * Update Shipping, Payment and resume
     *
     * @return void
     */
    public function update_spoAction()
    {
    	$data = $this->getRequest()->getPost('billing', array());
        $idAddress = null; 
        
        if ($data['use_for_shipping'] == '1') {
            if($this->getRequest()->getParam('billing_address_id') && intval($this->getRequest()->getParam('billing_address_id')) > 0) {
                $idAddress = $this->getRequest()->getParam('billing_address_id');
                $address = Mage::getModel('customer/address')->load($idAddress);
                $data['country_id'] = $address->getCountry();
            } else {
                $data = $this->getRequest()->getPost('billing', array());
            }
        }
        
        if ($data['use_for_shipping'] == '0') {
            if ($this->getRequest()->getParam('shipping_address_id') && intval($this->getRequest()->getParam('shipping_address_id')) > 0) {
                $idAddress = $this->getRequest()->getParam('shipping_address_id');
                $address = Mage::getModel('customer/address')->load($idAddress);
                $data['country_id'] = $address->getCountry();
            } else {
                $data = $this->getRequest()->getPost('shipping', array());
            }
        }
        
        if (!isset($data['country_id']) || empty($data['country_id'])) {
             $data['country_id'] = Mage::getStoreConfig('general/country/default');
        }
        
        $region = (!isset($data['region']) || empty($data['region'])) ? "-" : $data['region'];
        $region_id = (!isset($data['region_id']) || empty($data['region_id'])) ? "-" : $data['region_id'];
        $city = (!isset($data['city']) || empty($data['city'])) ? "-" : $data['city'];
        $postcode = (!isset($data['postcode']) || empty($data['postcode'])) ? "-" : $data['postcode'];

        $quote = $this->_getOnepage()->getQuote();
        $quote->getShippingAddress()
        	->setStreet($street)
            ->setCountryId($data['country_id'])
            ->setRegionId($region_id)
            ->setRegion($region)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setTelephone($telephone);
            
        $quote->setCollectShippingRates(true);
         
        $quote->getBillingAddress()->setCountryId($data['country_id']);
        
        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        if ($isLoggedIn) {
        	$result = $this->getOnepage()->saveShipping($data, $idAddress);
        } else {
        	$result = $this->getOnepage()->saveShipping($data, '');
        }
        
        $shippingMethod = $this->getRequest()->getParam('shipping_method');
        $resetShippingMethod = $this->getRequest()->getParam('reset_shipping_method');
        $shippingRates = $quote->getShippingAddress()->getAllShippingRates();
       	if (count($shippingRates) == 1) {
            $shippingMethod = $shippingRates[0]->getCode();
        } elseif($resetShippingMethod == "1") {
        	$shippingMethod = '';
        	$quote->getShippingAddress()->setShippingMethod('');
        }
		
        if ($shippingMethod != '') {
            $quote->getShippingAddress()->setShippingMethod($shippingMethod);
        }

        $quote->getShippingAddress()->collectShippingRates();
        $quote->setTotalsCollectedFlag(false);
		        
        $quote->collectTotals();
        $quote->save();
    
    	$this->loadLayout(false);
    	$this->renderLayout();
    }
    
    /**
     * Save order
     *
     * @return void
     */
    public function save_orderAction()
    {
    	$dataBilling = $this->getRequest()->getPost('billing', array());
    	$isSubscribed = $this->getRequest()->getPost('customer_subscribed', false);
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        $registered = $this->getRequest()->getParam('customer_register', false);
        
        try {
    		$result = $this->_getOnepage()->saveBilling($dataBilling, $customerAddressId);
	        
	        if (isset($result['error'])) {
	            throw new Exception($this->__("Please check billing address information"));
	        }
	        
	        // Billing address and shipping address are different
	        if (!isset($dataBilling['use_for_shipping']) || $dataBilling['use_for_shipping'] == "0") {
	            $dataShipping = $this->getRequest()->getPost('shipping', array());
	            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
	            $result = $this->_getOnepage()->saveShipping($dataShipping, $customerAddressId);
	            if (isset($result['error'])) {
	               throw new Exception($this->__("Please check shipping address information"));
	            }
	        }
	        
	        // Shipping Method
            $shippingMethod = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->_getOnepage()->saveShippingMethod($shippingMethod);
            if (isset($result['error'])) {
                throw new Exception($this->__('Select Shipping Method'));
            }
            
            // Section redirection Paypal
            // Save payment method
            $payment = $this->getRequest()->getPost('payment', array());
            if (isset($payment['method'])) {
            	$result = $this->_getOnepage()->savePayment($payment);
	            if (isset($result['error'])) {
	                throw new Exception($this->__('Payment method is not defined'));
	            }
            } else {
            	$result['error'] = true;
	    		$result['title'] = $this->__('Error');
	    		$result['message'] = $this->__('Please select a payment method');
	            $this->getResponse()->setBody(Zend_Json::encode($result));
	            return;
            }
            
            // get section and redirect data
            $redirectUrl = $this->_getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if ($redirectUrl) {
            	$result['redirect'] = $redirectUrl;
                $this->_getOnepage()->saveOrder();
                $lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
				if (isset($lastOrderId)) {
					$order = Mage::getModel('sales/order')->load($lastOrderId);
				}
            	$result['orderAmount'] = $order->getGrandTotal();
            	$result['incrementId'] = $order->getIncrementId();
            	$result['customerEmail'] = $order->getCustomerEmail();
            	$billingData = $order->getBillingAddress()->getData();
            	$billingData['street1'] = $order->getBillingAddress()->getStreet1();
            	$billingData['street2'] = $order->getBillingAddress()->getStreet2();
            	$result['billingAddress'] = $billingData;
            	$orderShippingData = $order->getShippingAddress()->getData();
            	$orderShippingData['country'] = Mage::app()->getLocale()->getCountryTranslation($orderShippingData['country_id']);
            	$result['shippingAddress'] = $orderShippingData;
                $this->getResponse()->setBody(Zend_Json::encode($result));
                return;
            }
            
            // Agreements
            if (Mage::getStoreConfig(self::XML_PATH_CHECKOUT_OPTIONS_ENABLE_AGREEMENTS)) {
            	$data = $this->getRequest()->getPost('agreement', array());
	            if (!isset($data[1]) || $data[1] != '1') {
	                throw new Exception($this->__('Please agree to all the terms and conditions before placing the order.'));
	            }	
            }
            
            if ($registered !== false) {
            	$this->_createCustomer($dataBilling, $isSubscribed);
            }
            
            $this->_getOnepage()->getQuote()->getPayment()->importData($payment);
            $this->_getOnepage()->saveOrder();
            
            
            $lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
			if (isset($lastOrderId)) {
				$order = Mage::getModel('sales/order')->load($lastOrderId);
			} else {
				throw new Exception($this->__('Your order could not be created. Please contact support.'));
			}
            
			
            $redirectUrl = $this->_getOnepage()->getCheckout()->getRedirectUrl();
            if ($redirectUrl == '') $redirectUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false) . "checkout/onepage/success";

            $result['success'] = true;
            $result['error']   = false;

            if (isset($redirectUrl)) {
                $result['redirect'] = $redirectUrl;
            }
            $this->getOnepage()->getQuote()->setIsActive(false);
            $this->getOnepage()->getQuote()->save();
            $this->getResponse()->setBody(Zend_Json::encode($result));

    	} catch (Exception $e) {
    		$result['error'] = true;
    		$result['title'] = $this->__('Error');
    		$result['message'] = $e->getMessage();
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
    	}
    }
    
    /**
     * Verify and set coupon code
     *
     * @return void
     */
    public function update_couponAction()
    {
    	$couponCode = (string) $this->getRequest()->getParam('coupon');
    	$response = array();
    	
    	try {
    		$this->_getCart()
    			->getQuote()
    			->getShippingAddress()
    			->setCollectShippingRates(true);
    		$this->_getCart()
    			->getQuote()
    			->setCouponCode(strlen($couponCode) ? $couponCode : '')
    			->collectTotals()
    			->save();
    			
    		if ($couponCode != $this->_getCart()->getQuote()->getCouponCode()) {
    			$response['error']= true;
    			$response['title'] = $this->__('Error');
    			$response['message'] = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
    			$this->getResponse()->setBody(Zend_Json::encode($response));
    			return;	
    		}
    		
    		$response['success'] = true;
    		$response['error'] = false;
    		$this->getResponse()->setBody(Zend_Json::encode($response));
    	} catch (Mage_Core_Exception $e) {
    		$response['error'] = true;
    		$response['title'] = $this->__('Error');
    		$response['message'] = $e->getMessage();
    		$this->getResponse()->setBody(Zend_Json::encode($response));
    		return;
    	} catch (Exception $e) {
    		$response['error'] = true;
    		$response['title'] = $this->__('Error');
    		$response['message'] = $e->getMessage();
    		$this->getResponse()->setBody(Zend_Json::encode($response));
    		return;
    	}
    }
    
    /**
     * Create Customer
     *
     * @param array $data
     * @return Mage_Customer_Model_Customer_Session
     */
    private function _createCustomer($data, $isSubscribed = false)
    {
    	$this->_getOnepage()->getQuote()->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
    }
    
    /**
     * Update product qty
     *
     * @return void
     */
    public function update_qtyAction() 
    {
    	$response = array();
    	$qty = (int)$this->getRequest()->getParam('qty');
    	$itemId = (int)$this->getRequest()->getParam('itemId');
    	
    	try {
    		$cart = $this->_getCart()->updateItems(array($itemId => array(
    			'qty' => $qty
    		)));
    		
    		$cart->save();
    		$response['success'] = true;
    		$this->getResponse()->setBody(Zend_Json::encode($response));
    		return;
    	} catch (Exception $e) {
    		$response['success'] = false;
    		$response['error'] = true;
    		$response['message'] = $e->getMessage();
    		$response['ddd'] = get_class($this->_getCart());
    		$this->getResponse()->setBody(Zend_Json::encode($response));
    		return;
    	}
    }
    
    /**
     * Product view
     *
     * @return void
     */
    public function product_viewAction()
    {
    	$product = Mage::getModel('catalog/product')->load($this->getRequest()->getParam('id', false));
    	$this->loadLayout(false);
    	$this->getLayout()->getBlock('directcheckout.product')->setProduct($product);
    	$this->renderLayout();
    }
    
    /**
	 * Gift Action
	 *
	 * @return void
	 */
	public function giftAction()
	{
		$this->loadLayout(false);
		$this->renderLayout();
	}
	
	/**
	 * Add gift options
	 *
	 * @return void
	 */
	public function giftPostAction()
	{
		$response = array();
		$quote = $this->_getOnepage()->getQuote();
		$data = $this->getRequest()->getParam('giftmessage');
		
		if (is_array($data)) {
			foreach ($data as $entityId => $message) {
				$giftMessage = Mage::getModel('giftmessage/message');
				
				switch ($message['type']) {
					case 'quote': 
						$entity = $quote;
						break;
					case 'quote_item':
						$entity = $quote->getItemById($entityId);
						break;
					case 'quote_address':
						$entity = $quote->getAddressById($message['address'])->getItemById($entityId);
						break;
					default:
						$entity = $quote;
						break;
				}
				
				if($entity->getGiftMessageId()) {
                    $giftMessage->load($entity->getGiftMessageId());
                }
                
                if(trim($message['message']) == '') {
                    if($giftMessage->getId()) {
                        try{
                            $giftMessage->delete();
                            $entity->setGiftMessageId(0)->save();
                        } catch (Exception $e) { 
                        	$response['success'] = false;
                        	$response['error'] = true;
                        	$response['message'] = $e->getMessage();
                        	$this->getResponse()->setBody(Zend_Json::encode($response));
                        }
                    }
                    continue;
                }
                
                try {
                    $giftMessage->setSender($message['from'])
                        ->setRecipient($message['to'])
                        ->setMessage($message['message'])
                        ->save();

                    $entity->setGiftMessageId($giftMessage->getId())->save();
                } catch (Exception $e) { 
                	$response['success'] = false;
                	$response['error'] = true;
                	$response['message'] = $e->getMessage();
                	$this->getResponse()->setBody(Zend_Json::encode($response));
                }
			}
		}
		
		$response['success'] = true;
    	$response['error'] = false;
    	$response['message'] = $this->__('Gift message was added');
    	$response['close'] = true;
    	$this->getResponse()->setBody(Zend_Json::encode($response));
	}
	
	/**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * @return void
     */
    public function manageGiftAction()
    {
    	$product_id = $this->_getGiftProductId();
        $add = $this->getRequest()->getPost('add', false);

        if($product_id) {
			if($add != "true") {
				$items = Mage::helper('checkout/cart')->getCart()->getItems();
                foreach($items as $item)    {
                    if($item->getProduct()->getId() == $product_id) {
                    	Mage::helper('checkout/cart')->getCart()->removeItem($item->getId())->save();
                    }
                }
            }
            else {
            	/* Add product to cart if it doesn't exist */
               	$params = array(
					'product' => $product_id,
					'related_product' => null,
					'options' => array(),
					'qty' => 1,
				);

				$product = Mage::getModel('catalog/product')->load($product_id);

				$cart = Mage::getSingleton('checkout/cart');
                $cart->addProduct($product, $params);
                $cart->save();
            }
        }

        $block = $this->getLayout()->createBlock('checkout/cart_totals')->setTemplate('directcheckout/checkout/summary.phtml')->toHtml();
        $result['summary'] = $block;

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /**
     * @return int|false
     */
    protected function _getGiftProductId()
    {
    	$product = Mage::helper('directcheckout')->getGiftProduct();

    	if (isset($product)) {
    		return $product->getId();
    	}

    	return false;
    }
}
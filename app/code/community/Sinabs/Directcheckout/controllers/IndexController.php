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
class Sinabs_Directcheckout_IndexController extends Mage_Core_Controller_Front_Action
{
	/**
	 * XML Path page title
	 *
	 */
	const XML_PATH_DIRECTCHECKOUT_GENERAL_PAGE_TITLE = 'directcheckout/general/page_title';

    /**
     * indexAction
     * @return void
     */
	public function indexAction()
	{
		if (!Mage::helper('customer')->isLoggedIn()) {
			 $this->_redirect('customer/account/login');
			 Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('directcheckout'));
	         return;
		}
		$quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
		$this->_clearQuoteShippingAddress($quote);
		$this->_clearPaymentMethod($quote);
		$data['country_id'] = Mage::getStoreConfig('general/country/default');
		
		if (!$quote->hasItems() || $quote->getHasError()) {
			$this->_redirect('checkout/cart');
			return;
		}
		
		if (!$quote->validateMinimumAmount()) {
			Mage::getSingleton('checkout/session')->addError(Mage::getStoreConfig('sales/minimum_order/error_message'));
			$this->_redirect('checkout/cart');
			return;
		}
		
		if (!Mage::helper('directcheckout')->isEnabled()) {
			$this->_redirect('checkout');
			return;
		}
		
		Mage::helper('directcheckout/data')->setDefaultShipping($data);
		
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle(Mage::getStoreConfig(self::XML_PATH_DIRECTCHECKOUT_GENERAL_PAGE_TITLE));
		$this->renderLayout();
	}

    /**
     * Clear quote shipping address
     * @param $quote
     * @return void
     */
	protected function _clearQuoteShippingAddress($quote)
	{
		$shippingAddress = $quote->getShippingAddress();
		$shippingAddress->setStreet('')
			->setPostcode('')
			->setCountryId('');
			
		$billingAddress = $quote->getBillingAddress();
		$billingAddress->setStreet('')
			->setPostcode('')
			->setCountryId('');
			
		$quote->getShippingAddress()->setShippingMethod('');
		
		$quote->save();
	}

    /**
     * Clear quote payment method
     * @param $quote
     * @return void
     */
	protected function _clearPaymentMethod($quote)
	{
		$quote->getPayment()->setMethod(null)->save();
	}
}
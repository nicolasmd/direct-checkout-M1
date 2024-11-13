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
class Sinabs_Directcheckout_Block_Checkout_Coupon extends Mage_Core_Block_Template
{

	const XML_PATH_DIRECTCHECKOUT_EXTRA_ENABLE_COUPON = 'directcheckout/extra/enable_coupon';

    /**
     * @return Mage_Sales_Model_Quote
     */
	protected function _getQuote()
	{
		return Mage::getSingleton('checkout/cart')->getQuote();
	}

    /**
     * @return bool
     */
	public function isEnabled()
	{
		return Mage::getStoreConfig(self::XML_PATH_DIRECTCHECKOUT_EXTRA_ENABLE_COUPON);
	}

    /**
     * @return bool
     */
	public function isCouponEnable()
	{
		return (string)$this->_getQuote()->getCouponCode() != '';
	}

    /**
     * @return string
     */
	public function getCouponCode()
	{
		return $this->_getQuote()->getCouponCode();
	}
}
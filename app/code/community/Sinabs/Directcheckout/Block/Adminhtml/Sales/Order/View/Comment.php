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
class Sinabs_Directcheckout_Block_Adminhtml_Sales_Order_View_Comment extends Mage_Adminhtml_Block_Sales_Order_View_Items
{
    public function _toHtml(){
        $html = parent::_toHtml();
        $comment = $this->getCommentHtml();
        return $html.$comment;
    }

    /**
     * Get comment from order and return as html formatted string
     *
     * @return string
     */
    public function getCommentHtml(){

    	$order = $this->getOrder();
    	
    	//Gift message
        $comment = $order->getOnestepcheckoutCustomercomment();
        $feedback = $order->getOnestepcheckoutCustomerfeedback();

        $html = '';

        if ($this->isShowCustomerCommentEnabled() && $comment){
            $html .= '<div id="customer_comment" class="giftmessage-whole-order-container"><div class="entry-edit">';
            $html .= '<div class="entry-edit-head"><h4>'.$this->helper('directcheckout')->__('Customer Comment').'</h4></div>';
            $html .= '<fieldset>'.nl2br($this->helper('directcheckout')->htmlEscape($comment)).'</fieldset>';
            $html .= '</div></div>';
        }

        if($this->isShowCustomerFeedbackEnabled()){
            $html .= '<div id="customer_feedback" class="giftmessage-whole-order-container"><div class="entry-edit">';
            $html .= '<div class="entry-edit-head"><h4>'.$this->helper('directcheckout')->__('Customer Feedback').'</h4></div>';
            $html .= '<fieldset>'.nl2br($this->helper('directcheckout')->htmlEscape($feedback)).'</fieldset>';
            $html .= '</div></div>';
        }

        return $html;
    }

    /**
     * @return string|null
     */
    public function isShowCustomerCommentEnabled(){
    	return Mage::getStoreConfig('onestepcheckout/exclude_fields/enable_comments', $this->getOrder()->getStore());
    }

    /**
     * @return string|null
     */
    public function isShowCustomerFeedbackEnabled(){
    	return Mage::getStoreConfig('onestepcheckout/feedback/enable_feedback', $this->getOrder()->getStore());
    }
}

<?php
namespace Aichat\CommerceTemplate\Model\ResourceModel\Checkout;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'id';
	protected $_eventPrefix = 'aichat_commercetemplate_checkout_collection';
	protected $_eventObject = 'checkout_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Aichat\CommerceTemplate\Model\Checkout', 'Aichat\CommerceTemplate\Model\ResourceModel\Checkout');
	}

}

<?php
namespace Aichat\CommerceTemplate\Model\ResourceModel;


class Checkout extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}

	protected function _construct()
	{
		$this->_init('aichat_commercetemplate_checkout', 'id');
	}

}

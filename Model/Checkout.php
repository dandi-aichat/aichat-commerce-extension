<?php
namespace Aichat\CommerceTemplate\Model;
class Checkout extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'aichat_commercetemplate_checkout';

	protected $_cacheTag = 'aichat_commercetemplate_checkout';

	protected $_eventPrefix = 'aichat_commercetemplate_checkout';

	protected function _construct()
	{
		$this->_init(ResourceModel\Checkout::class);
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}

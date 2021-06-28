<?php
namespace Aichat\CommerceTemplate\Model;
class Config extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'aichat_commercetemplate_config';

	protected $_cacheTag = 'aichat_commercetemplate_config';

	protected $_eventPrefix = 'aichat_commercetemplate_config';

	protected function _construct()
	{
		$this->_init(ResourceModel\Config::class);
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

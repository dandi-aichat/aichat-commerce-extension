<?php
namespace Aichat\CommerceTemplate\Model\ResourceModel\Config;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'config_id';
	protected $_eventPrefix = 'aichat_commercetemplate_config_collection';
	protected $_eventObject = 'config_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Aichat\CommerceTemplate\Model\Config', 'Aichat\CommerceTemplate\Model\ResourceModel\Config');
	}

}

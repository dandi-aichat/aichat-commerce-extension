<?php
namespace Aichat\CommerceTemplate\Block\Adminhtml;

use Aichat\CommerceTemplate\Model\ConfigFactory;

class Webhook extends \Magento\Framework\View\Element\Template
{
    protected $resource;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        parent::__construct($context);

        $this->resource = $resource;
    }

    /**
     * Get form action URL for POST form request
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('commercetemplate/webhook/index');
    }

    public function getHookConfig($hookType){

        $connection  = $this->resource->getConnection();
        $tableName = $connection->getTableName("aichat_commercetemplate_config");

        $select = $connection->select()->from($tableName, 'config_value')->where('config_key = :config_key');
        $data = $connection->fetchOne($select, [':config_key' => $hookType]);

        if($data){
            return $data;
        }
        return "<please set>";
    }
}

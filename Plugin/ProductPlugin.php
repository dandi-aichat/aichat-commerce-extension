<?php
namespace Aichat\CommerceTemplate\Plugin;

use Magento\Framework\Event\ManagerInterface as EventManager;

class ProductPlugin{
    /**
    * @var EventManager
    */
    private $eventManager;

    public function __construct(EventManager $eventManager){
        $this->eventManager = $eventManager;
    }

    public function afterSave(\Magento\Catalog\Api\ProductRepositoryInterface $subject, \Magento\Catalog\Api\Data\ProductInterface $product, $saveOptions){

        $this->eventManager->dispatch('aichat_commercetemplate_restapi_product_update_after', ['product' => $product, 'rest_api_action' => 'update']);

        return $product;
    }
}

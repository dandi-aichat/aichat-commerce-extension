<?php
namespace Aichat\CommerceTemplate\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class Product implements ObserverInterface
{
    protected $productRepository;
    protected $resource;
    protected $json;
    protected $categoryCollectionFactory;
    protected $currencyFactory;
    protected $scopeConfig;
    protected $store;
    protected $aicConfig;
    protected $logger;
    protected $curl;
    protected $getSalableQuantityDataBySku;
    protected $_categoryFactory;
    protected $_attributeSet;
    protected $_exportMainAttrCodes = [
        'sku',
        'name',
        'description',
        'short_description',
        'weight',
        'product_online',
        'tax_class_name',
        'visibility',
        'price',
        'special_price',
        'special_price_from_date',
        'special_price_to_date',
        'url_key',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'base_image',
        'base_image_label',
        'small_image',
        'small_image_label',
        'thumbnail_image',
        'thumbnail_image_label',
        'swatch_image',
        'swatch_image_label',
        'created_at',
        'updated_at',
        'new_from_date',
        'new_to_date',
        'display_product_options_in',
        'map_price',
        'msrp_price',
        'map_enabled',
        'special_price_from_date',
        'special_price_to_date',
        'gift_message_available',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'page_layout',
        'product_options_container',
        'msrp_price',
        'msrp_display_actual_price_type',
        'map_enabled',
        'country_of_manufacture',
        'map_price',
        'display_product_options_in',
    ];
    protected $_attributeColFactory;

    protected function getHookUrl(){
        $collections = $this->aicConfig->create()->getCollection();
        $collections = $collections->addFieldToFilter('config_key', 'product_hook_url');
        $data = $collections->getData();

        return (count($data) > 0 ? array_values($data)[0] : false);
    }

    protected function sendPayload($url, $data){
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, $data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getSalableQty($sku){
        $salable = $this->getSalableQuantityDataBySku->execute($sku);
        $totalSalable = 0;
        foreach($salable as $salableInfo){
            $totalSalable += $salableInfo['qty'];
        }
        return $totalSalable;
    }

    private function getCategoryName($categoryId)
    {
        $category = $this->_categoryFactory->create()->load($categoryId);
        $categoryName = $category->getName();
        return $categoryName;
    }

    private function getAttributeSetName($attrId){
        $attributeSetRepository = $this->_attributeSet->get($attrId);
        $attribute_set_name = $attributeSetRepository->getAttributeSetName();
        return $attribute_set_name;
    }

    private function getRelatedProducts($relatedProducts){
        $products = array();
        foreach($relatedProducts as $product){
            $products[] = $product->getSku();
        }
        return $products;
    }

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
      \Magento\Framework\App\ResourceConnection $resource,
      \Magento\Framework\Serialize\Serializer\Json $json,
      \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
      \Magento\Directory\Model\CurrencyFactory $currencyFactory,
      \Magento\Store\Model\StoreManagerInterface $storeConfig,
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
      \Aichat\CommerceTemplate\Model\ConfigFactory $aicConfig,
      \Psr\Log\LoggerInterface $logger,
      \Magento\Framework\HTTP\Client\Curl $curl,
      \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
      \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
      \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
      \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    )
    {
        $this->resource = $resource;
        $this->json = $json;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->store = $storeConfig->getStore();
        $this->currencyFactory = $currencyFactory->create()->load($this->store->getCurrentCurrencyCode());
        $this->scopeConfig = $scopeConfig;
        $this->aicConfig = $aicConfig;
        $this->logger = $logger;
        $this->curl = $curl;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->_categoryFactory = $categoryFactory;
        $this->_attributeSet = $attributeSet;
        $this->_attributeColFactory = $attributeColFactory;
        $this->productRepository = $productRepository;

        $this->curl->setOption(CURLOPT_TIMEOUT, 3);
    }

    private function sendUpdate($product, $urlData, $observer, $childProduct = FALSE){
        if(!empty($urlData)){
            $url = $urlData["config_value"];
            $newProduct = $product->getData();
            $oldProduct = $product->getOrigData();

            $action = empty($oldProduct['entity_id']) || $observer->getData('rest_api_action') == 'create' ?
                        'CREATE' :
                        ((!empty($newProduct['_edit_mode']) || $observer->getData('rest_api_action') == 'update') ? 'UPDATE' : 'DELETE');

            $action = $childProduct == TRUE ? 'UPDATE' : $action;

            if($action != 'DELETE'){
                $newProduct['currency'] = [
                    'code' => $this->currencyFactory->getCurrencyCode(),
                    'symbol' => $this->currencyFactory->getCurrencySymbol()
                ];

                $newProduct['weight_label'] = $this->scopeConfig->getValue('general/locale/weight_unit', ScopeInterface::SCOPE_STORE);
                $newProduct['base_url'] = $this->store->getBaseUrl();
                $newProduct['product_link'] = $this->store->getBaseUrl() . $product->getUrlKey() . '.html';

                if($newProduct['type_id'] != 'configurable')
                    $newProduct['salable_qty'] = $this->getSalableQty($newProduct['sku']);

                if(!empty($newProduct['attribute_set_id'])){
                    $newProduct['attribute_set_name'] = $this->getAttributeSetName($newProduct['attribute_set_id']);
                }
                if(!empty($newProduct['category_ids'])){
                    // get an instance of CategoryCollection
                   $categoryCollection = $this->categoryCollectionFactory->create();

                   // add a filter to get the IDs you need
                   $categoryCollection->addFieldToFilter('entity_id', $newProduct['category_ids']);

                   $newProduct['categories'] = array();
                    foreach ($categoryCollection->getItems() as $category) {
                        $catData = $category->getData();
                        $catData['name'] = $this->getCategoryName($category->getId());
                        $catData['parent_name'] = $this->getCategoryName($category->getParentId());
                        $newProduct['categories'][] = $catData;
                    }
                }

                if(!empty($newProduct['cross_sell_products'])){
                    $newProduct['cross_sell_products'] = $this->getRelatedProducts($product->getCrossSellProductCollection());
                }
                if(!empty($newProduct['related_products'])){
                    $newProduct['related_products'] = $this->getRelatedProducts($product->getRelatedProductCollection());
                }
                if(!empty($newProduct['up_sell_products'])){
                    $newProduct['up_sell_products'] = $this->getRelatedProducts($product->getUpSellProductCollection());
                }

                $additionalAttr = array();
                $attributes = $product->getAttributes();
                if($attributes !== null){
                    $productAttributes = array();
                    foreach($attributes as $attribute){

                        if ($attribute->getIsVisibleOnFront()) {
                            $attributeCode = $attribute->getAttributeCode();
                            $additionalAttr[$attributeCode] = $attribute->usesSource() ? $attribute->getSource()->getOptionText($product->getData($attributeCode)) : $product->getData($attributeCode);
                        }
                        else {
                            $attributeCode = $attribute->getAttributeCode();
                            $defaultProductAttribute = $product->getData($attributeCode);
                            $productAttributes[$attributeCode] = $attribute->usesSource() && !empty($attribute->getSource()->getOptionId($attributeCode)) ? $attribute->getSource()->getOptionText($defaultProductAttribute) : $defaultProductAttribute;
                        }
                    }
                    $newProduct['additional_attributes'] = $additionalAttr;
                    $newProduct['attributes'] = $productAttributes;
                }


                $newProduct['product_online'] = $newProduct['status'];

                if($newProduct['type_id'] == 'configurable'){
                    // print_r(get_class_methods($product));
                    $configurableProduct = $product->getTypeInstance();
                    $productVariants = $configurableProduct->getUsedProducts($product);
                    $productVariantsLabel = $configurableProduct->getUsedProductAttributes($product);

                    $variantLabel = array();
                    $configurableVariants = array();
                    foreach($productVariants as $productVar){
                        $pvarData = array(
                            'sku' => $productVar->getSku()
                        );

                        foreach($productVariantsLabel as $varLabel){
                            $lcode = $varLabel->getAttributeCode();
                            $pvarData[$lcode] = $varLabel->getSource()->getOptionText($productVar->getData($lcode));
                        }

                        $configurableVariants[] = $pvarData;

                        $childProductData = $this->productRepository->get($productVar->getSku());
                        $this->sendUpdate($childProductData, $urlData, $observer, TRUE);
                    }
                    foreach($productVariantsLabel as $varLabel){
                        $variantLabel[] = $varLabel->getAttributeCode() . "=" . $varLabel->getStoreLabel();
                    }

                    $newProduct['configurable_variations'] = $configurableVariants;
                    $newProduct['configurable_variation_labels'] = $variantLabel;
                }
            }

            $productUpdate = array(
                'data' => $newProduct,
                'origData' => $oldProduct,
                'action' => $action
            );

            $this->sendPayload($url, $this->json->serialize($productUpdate));
        }
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Observer execution code...
        $product = $observer->getProduct();

        $urlData = $this->getHookUrl();

        $this->sendUpdate($product, $urlData, $observer);

        return $this;
    }
}

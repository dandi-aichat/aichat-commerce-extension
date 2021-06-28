<?php
    namespace Aichat\CommerceTemplate\Controller\Product;

    class Index extends \Magento\Framework\App\Action\Action
    {
        protected $_pageFactory;
        protected $_responseFactory;
        protected $_url;
        protected $_resource;
        protected $_productRepository;
        protected $_productFactory;
        protected $_configurableFactory;
        protected $logger;

        protected function findProduct($key, $value){
            $result = $this->_productFactory->create()
                ->getCollection()
                ->addAttributeToFilter($key, $value)
                ->getData();
            return count($result) ? $result[0] : false;
        }
        protected function getProduct($id){
            return $this->_productRepository->getById($id);
        }

    	public function __construct(
    		\Magento\Framework\App\Action\Context $context,
    		\Magento\Framework\View\Result\PageFactory $pageFactory,
            \Magento\Framework\App\ResponseFactory $responseFactory,
            \Magento\Framework\UrlInterface $url,
            \Magento\Catalog\Model\ProductRepository $productRepository,
            \Magento\Catalog\Model\ProductFactory $productFactory,
            \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory $configurableFactory,
            \Psr\Log\LoggerInterface $logger
            )
    	{
    		$this->_pageFactory = $pageFactory;
            $this->_responseFactory = $responseFactory;
            $this->_url = $url;
            $this->_productRepository = $productRepository;
            $this->_productFactory = $productFactory;
            $this->_configurableFactory = $configurableFactory;
            $this->logger = $logger;

    		return parent::__construct($context);
    	}

    	public function execute()
    	{
            $urlKey = $this->getRequest()->getParam('key');

            if(empty($urlKey)){
                $this->logger->error("Invalid parameters.");
            }
            else {
                $product = $this->findProduct('url_key', $urlKey);

                if(empty($product)){
                    $this->logger->error("Product not found. key: " . $urlKey);
                }
                else {
                    $productIds = $this->_configurableFactory->create()->getParentIdsByChild($product['entity_id']);

                    if(count($productIds) > 0){
                        $productId = $productIds[0];

                        $parentProduct = $this->findProduct('entity_id', $productId);
                        $parentProductData = $this->getProduct($parentProduct['entity_id']);

                        if(empty($parentProductData)){
                            $this->logger->error("Parent Product not found. key: " . $urlKey);
                        }
                        else{
                            $productUrl = $this->_url->getUrl($parentProductData->getUrlKey() . '.html');

                            $this->_responseFactory->create()->setRedirect($productUrl)->sendResponse();
                        }
                    }
                    else {
                        $productUrl = $this->_url->getUrl($urlKey . '.html');

                        $this->_responseFactory->create()->setRedirect($productUrl)->sendResponse();
                    }
                }
            }
    	}
    }

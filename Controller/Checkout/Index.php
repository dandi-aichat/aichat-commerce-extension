<?php
    namespace Aichat\CommerceTemplate\Controller\Checkout;

    use Magento\Framework\App\Http\Context as AuthContext;

    class Index extends \Magento\Framework\App\Action\Action
    {
        protected $_pageFactory;
        protected $_quoteFactory;
        protected $_session;
        protected $_responseFactory;
        protected $_url;
        protected $_resource;
        protected $_checkoutFactory;
        protected $logger;
        protected $customerRepo;
        protected $customerFactory;
        protected $customerSession;
        private $authContext;

        protected function getQuote($id){
            $collections = $this->_quoteFactory->create()->getCollection();
            $collections = $collections->addFieldToFilter('entity_id', $id);
            $data = $collections->getData();
            return count($data) > 0 ? array_values($data)[0] : false;
        }

        protected function getCheckout($id){
            $collections = $this->_checkoutFactory->create()->getCollection();
            $collections = $collections->addFieldToFilter('quote_id', $id);
            $data = $collections->getData();
            return count($data) > 0 ? array_values($data)[0] : false;
        }

    	public function __construct(
    		\Magento\Framework\App\Action\Context $context,
    		\Magento\Framework\View\Result\PageFactory $pageFactory,
            \Magento\Quote\Model\QuoteFactory $quoteFactory,
            \Magento\Checkout\Model\Session $session,
            \Magento\Framework\App\ResponseFactory $responseFactory,
            \Magento\Framework\UrlInterface $url,
            \Magento\Framework\App\ResourceConnection $resource,
            \Aichat\CommerceTemplate\Model\CheckoutFactory $checkoutFactory,
            \Psr\Log\LoggerInterface $logger,
            // customer session
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepo,
            \Magento\Customer\Model\CustomerFactory $customerFactory,
            \Magento\Customer\Model\Session $customerSession,
            AuthContext $authContext
            )
    	{
    		$this->_pageFactory = $pageFactory;
            $this->_quoteFactory = $quoteFactory;
            $this->_session = $session;
            $this->_responseFactory = $responseFactory;
            $this->_url = $url;
            $this->_resource = $resource;
            $this->_checkoutFactory = $checkoutFactory;
            $this->logger = $logger;

            $this->customerRepo = $customerRepo;
            $this->customerFactory = $customerFactory;
            $this->customerSession = $customerSession;
            $this->authContext = $authContext;

    		return parent::__construct($context);
    	}

    	public function execute()
    	{
            $id = $this->getRequest()->getParam('id');

            if(!empty($id) && is_int(intval($id)) && !empty($this->getQuote($id))){
                // marked as aichat checkout
                if(empty($this->getCheckout($id))){
                    $checkoutData = $this->_checkoutFactory->create();
                    $checkoutData->setQuoteId($id);
                    $checkoutData->save();
                }

                $quote = $this->getQuote($id);

				$cartUrl = $this->_url->getUrl('checkout/cart/index');
				$loginUrl = $this->_url->getUrl('customer/account/login');

                if(!empty($quote["customer_id"])){
                    $redirectUrl = '';
                    $customerId = $quote["customer_id"];

                    $isLoggedIn = $this->authContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);

                    if($this->customerSession->isLoggedIn() && $isLoggedIn){
                        $loggedInId = $this->customerSession->getCustomerId();

                        if($loggedInId == $customerId){
                            $redirectUrl = $cartUrl;
                        }
                        else {
                            $this->customerSession->logout();
							$this->customerSession->setBeforeAuthUrl($cartUrl);
                            $redirectUrl = $loginUrl;
                        }
                    }
                    else {
						$this->customerSession->setBeforeAuthUrl($cartUrl);
                        $redirectUrl = $loginUrl;
                    }
                    $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
                }
                else {
                    $this->_session->setQuoteId($id);
                    $this->_responseFactory->create()->setRedirect($cartUrl)->sendResponse();
                }
            }
            else {
                $this->logger->error("quote not found, id: " . $id);
            }

            // login example
            // $customerRepo = $this->customerRepo->get("test@mail.com");
            // $customer = $this->customerFactory->create()->load($customerRepo->getId());
            // $this->customerSession->setCustomerAsLoggedIn($customer);
    	}
    }

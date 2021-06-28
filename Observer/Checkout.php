<?php
namespace Aichat\CommerceTemplate\Observer;

use Magento\Framework\Event\ObserverInterface;

class Checkout extends Notification implements ObserverInterface
{
    private $orderRepository;

    public function __construct(
        \Aichat\CommerceTemplate\Model\ConfigFactory $aicConfig,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        \Aichat\CommerceTemplate\Model\CheckoutFactory $checkoutFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
        )
    {
        $this->orderRepository = $orderRepository;
        parent::__construct($aicConfig, $json, $curl, $logger, $quoteIdToMaskedQuoteId, $checkoutFactory);
    }

    protected function sendPayload($url, $data){
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, $data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $url = $this->getHookUrl();

        if(!empty($url)){
            $idList = $observer->getOrderIds();
            $orderId = $idList[0];
            $order = $this->orderRepository->get($orderId);

            $quoteId = $order->getQuoteId();
            $maskedQuoteId = $this->getQuoteMaskId($quoteId);

            if($this->isAichat($quoteId)){
                $data = [
                    'type' => 'checkout',
                    'order_ids' => $observer->getOrderIds(),
                    'order' => [
                        'id' => $order->getId(),
                        'real_order_id' => $order->getRealOrderId(),
                        'quote_id' => empty($maskedQuoteId) ? $quoteId : $maskedQuoteId,
                        'quote_id_masked' => $maskedQuoteId,
                        'quote_id_original' => $quoteId,
                        'status' => $order->getStatusLabel(),
                        'email' => $order->getCustomerEmail(),
                        'items' => $this->getOrderItems($order),
                        'created_at' => $order->getCreatedAt()
                    ]
                ];
                $jsonData = json_encode($data);

                $this->sendPayload($url, $jsonData);
            }
        }

        return $this;
    }
}

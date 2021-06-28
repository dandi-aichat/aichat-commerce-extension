<?php
namespace Aichat\CommerceTemplate\Observer;

use Magento\Framework\Event\ObserverInterface;

class Notification
{
    protected $aicConfig;
    protected $curl;
    protected $json;
    protected $logger;
    protected $quoteIdToMaskedQuoteId;
    protected $checkoutFactory;

    protected function getHookUrl(){
        $collections = $this->aicConfig->create()->getCollection();
        $collections = $collections->addFieldToFilter('config_key', 'notification_hook_url');
        $data = $collections->getData();

        return (count($data) > 0 ? array_values($data)[0]["config_value"] : false);
    }

    protected function isAichat($id){
        $collections = $this->checkoutFactory->create()->getCollection();
        $collections = $collections->addFieldToFilter('quote_id', $id);
        $data = $collections->getData();
        return count($data) > 0;
    }

    protected function getQuoteMaskId($quoteId)
    {
        $maskedId = null;

        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $this->logger->info("masked quote id $quoteId not found.");
        }

        return $maskedId;
    }

    protected function getOrderItems($order){
        $allItems = $order->getAllItems();
        // exclude parent item
        $orderedItems = array_filter($allItems, function($item){
            return $item->getProductType() != 'configurable';
        });
        $itemsData = [];
        foreach($orderedItems as $item){
            $itemsData[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'qty' => (double)$item->getQtyOrdered(),
                'price' => $item->getPrice()
            ];
        }
        return $itemsData;
    }

    protected function sendPayload($url, $data){
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, $data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function __construct(
        \Aichat\CommerceTemplate\Model\ConfigFactory $aicConfig,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        \Aichat\CommerceTemplate\Model\CheckoutFactory $checkoutFactory
        )
    {
        $this->aicConfig = $aicConfig;
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->checkoutFactory = $checkoutFactory;

        $this->curl->setOption(CURLOPT_TIMEOUT, 3);
    }
}

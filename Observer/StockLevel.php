<?php
namespace Aichat\CommerceTemplate\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;

class StockLevel extends Notification implements ObserverInterface
{
    private $getSalableQuantityDataBySku;
    private $orderRepository;

    private function getSalableQty($sku){
        $salable = $this->getSalableQuantityDataBySku->execute($sku);
        $totalSalable = 0;
        foreach($salable as $salableInfo){
            $totalSalable += $salableInfo['qty'];
        }
        return $totalSalable;
    }

    public function __construct(
        \Aichat\CommerceTemplate\Model\ConfigFactory $aicConfig,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        \Aichat\CommerceTemplate\Model\CheckoutFactory $checkoutFactory,
        \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
        )
    {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->orderRepository = $orderRepository;
        parent::__construct($aicConfig, $json, $curl, $logger, $quoteIdToMaskedQuoteId, $checkoutFactory);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $url = $this->getHookUrl();
        $event = $observer->getEvent();

        if(!empty($url)){
            $data = ['type' => 'stock_level'];

            if($event->getName() == "checkout_onepage_controller_success_action")
            {
                $idList = $observer->getOrderIds();
                $orderId = $idList[0];
                $order = $this->orderRepository->get($orderId);

                $itemsCollection = $order->getAllItems();
                $stockData = array();

                foreach ($itemsCollection as $item) {
                    if($item->getProductType() == 'configurable'){
                        continue;
                    }

                    $productSku = $item->getSku();
                    $salableQty = $this->getSalableQty($productSku);

                    if($salableQty <= 0){
                        $stockData[] = [
                            'sku' => $productSku,
                            'salable_qty' => $salableQty,
                            'available' => false
                        ];
                    }
                }
                if(count($stockData) > 0)
                    $data['products'] = $stockData;
            }
            else if($event->getName() == "sales_order_item_cancel")
            {
                $item = $observer->getItem();

                $salableQty = $this->getSalableQty($item->getSku());

                if($item->getQtyOrdered() == $salableQty){
                    $data['products'] = [
                        [
                            'sku' => $item->getSku(),
                            'salable_qty' => $salableQty,
                            'available' => true
                        ]
                    ];
                }
            }

            if(array_key_exists('products', $data)){
                $this->sendPayload($url, $this->json->serialize($data));
            }
        }

        return $this;
    }
}

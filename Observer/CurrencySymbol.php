<?php
namespace Aichat\CommerceTemplate\Observer;

use Magento\Framework\Event\ObserverInterface;

class CurrencySymbol implements ObserverInterface
{
    private $aicConfig;
    private $json;
    private $curl;
    private $logger;
    private $priceCurrency;

    public function __construct(
        \Aichat\CommerceTemplate\Model\ConfigFactory $aicConfig,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    )
    {
        $this->aicConfig = $aicConfig;
        $this->json = $json;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;

        $this->curl->setOption(CURLOPT_TIMEOUT, 3);
    }

    protected function getHookUrl(){
        $collections = $this->aicConfig->create()->getCollection();
        $collections = $collections->addFieldToFilter('config_key', 'notification_hook_url');
        $data = $collections->getData();

        return (count($data) > 0 ? array_values($data)[0]["config_value"] : false);
    }

    protected function sendPayload($url, $data){
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, $data);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function getCurrencySymbol($storeId)
    {
        // $currencySymbol = $this->priceCurrency->getCurrencySymbol(); // DEFAULT STORE CURRENCY SYMBOL
 
        $currencySymbol = $this->priceCurrency->getCurrencySymbol($storeId); // CURRENCY SYMBOL by store id
 
        // $currencySymbol = $this->priceCurrency->getCurrencySymbol('default'); // CURRENCY SYMBOL by store code
 
        return $currencySymbol;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = $observer->getStore();

        $currencySymbol = $this->getCurrencySymbol($storeId);

        $payload = array(
            'type' => 'currency',
            'currency_symbol' => $currencySymbol
        );

        $url = $this->getHookUrl();

        if(!empty($url)){
            $this->sendPayload($url, $this->json->serialize($payload));
        }
    }
}

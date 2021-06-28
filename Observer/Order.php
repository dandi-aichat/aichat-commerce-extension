<?php
namespace Aichat\CommerceTemplate\Observer;

use Magento\Framework\Event\ObserverInterface;

class Order extends Notification implements ObserverInterface
{
    private $_countryFactory;

    public function __construct(
        \Aichat\CommerceTemplate\Model\ConfigFactory $aicConfig,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        \Aichat\CommerceTemplate\Model\CheckoutFactory $checkoutFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
        )
    {
        $this->_countryFactory = $countryFactory;
        parent::__construct($aicConfig, $json, $curl, $logger, $quoteIdToMaskedQuoteId, $checkoutFactory);
    }

    public function getCountryname($countryCode){
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $url = $this->getHookUrl();

        if(!empty($url)){

            $order = $observer->getOrder();

            // get payment method
            $payment = $order->getPayment();
            $payment_method = $payment->getMethodInstance();
            $methodTitle = $payment_method->getTitle();

            //get shipping address
            $shippingAddress = $order->getShippingAddress();

            $quoteId = $order->getQuoteId();
            $maskedQuoteId = $this->getQuoteMaskId($quoteId);

            if($this->isAichat($quoteId)){
                $city = !empty($shippingAddress) ? $shippingAddress->getCity() : '';
                $state = !empty($shippingAddress) ? $shippingAddress->getRegion() : '';
                $street = !empty($shippingAddress) ? $shippingAddress->getStreet() : '';
                $address_type = !empty($shippingAddress) ? $shippingAddress->getAddressType() : '';
                $fax = !empty($shippingAddress) ? $shippingAddress->getFax() : '';
                $post_code = !empty($shippingAddress) ? $shippingAddress->getPostCode() : '';
                $country = !empty($shippingAddress) ? $this->getCountryname($shippingAddress->getCountryId()) : '';

                $data = [
                    'type' => 'order',
                    'order_ids' => $observer->getOrderIds(),
                    'order' => [
                        'id' => $order->getId(),
                        'quote_id' => empty($maskedQuoteId) ? $quoteId : $maskedQuoteId,
                        'quote_id_masked' => $maskedQuoteId,
                        'quote_id_original' => $quoteId,
                        'status' => $order->getStatusLabel(),
                        'email' => $order->getCustomerEmail(),
                        'items' => $this->getOrderItems($order),
                        'created_at' => $order->getCreatedAt()
                    ],
                    'payment_method' => $methodTitle,
                    'shipping_address' => [
                        'street' => $street,
                        'address_type' => $address_type,
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'fax' => $fax,
                        'post_code' => $post_code
                    ],
                    'sub_total_incl_tax' => $order->getSubtotalInclTax(),
                    'shipping_cost_incl_tax' => $order->getShippingInclTax(),
                    'total_cost' => $order->getGrandTotal(),
                    'discount' => $order->getDiscountAmount(),
                    'currency' => $order->getOrderCurrencyCode(),
                    'currency_symbol' => $order->getOrderCurrency()->getCurrencySymbol()
                ];
                $jsonData = json_encode($data);

                $this->sendPayload($url, $jsonData);
            }
        }

        return $this;
    }
}

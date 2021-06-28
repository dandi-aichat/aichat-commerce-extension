<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Aichat\CommerceTemplate\Model\Config;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    public function getData()
    {
        return [
            'webhook' => [
                'id' => 1,
                'product_hook_url' => '-',
                'checkout_hook_url' => '-'
            ]
        ];
    }
}

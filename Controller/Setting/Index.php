<?php
namespace Aichat\CommerceTemplate\Controller\Setting;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Aichat\CommerceTemplate\Model\ConfigFactory;

/**
 * Class Index
 */
class Index extends Action
{
    protected $configFactory;
    protected $jsonFactory;

    protected function getHookConfig($hookType){
        $collections = $this->configFactory->create()->getCollection();
        $collections = $collections->addFieldToFilter('config_key', $hookType);
        $data = $collections->getData();

        return (count($data) > 0 ? array_values($data)[0] : false);
    }

    protected function getConfigs(){
        $collections = $this->configFactory->create()->getCollection();
        $data = $collections->getData();

        return array_values($data);
    }

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ConfigFactory $configFactory
    ) {
        parent::__construct($context);

        $this->jsonFactory = $jsonFactory;
        $this->configFactory = $configFactory;
    }

    public function execute()
    {
        if($this->getRequest()->getMethod() == 'POST'){
            $posts = $this->getRequest()->getPostValue();
            
            foreach ($posts as $key => $value) {
                $config = $this->configFactory->create();
                $configData = $this->getHookConfig($key);

                if($configData)
                {
                    $configId = $configData['config_id'];
                    $configUpdate = $config->load($configId);
                    $configUpdate->setConfigValue($value);
                    $configUpdate->save();
                }
                else
                {
                    $config->setData([
                        'config_key' => $key,
                        'config_value' => $value
                    ]);
                    $config->save();
                }
            }
        }

        $aichatConfigs = $this->getConfigs();

        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData(['result' => 'ok', 'configs' => $aichatConfigs]);
    }
}

<?php
namespace Aichat\CommerceTemplate\Controller\Adminhtml\Webhook;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Aichat\CommerceTemplate\Model\ConfigFactory;

/**
 * Class Index
 */
class Index extends Action
{
    const MENU_ID = 'Aichat_CommerceTemplate::webhook';

    protected $resultPageFactory;
    protected $configFactory;

    protected function getHookConfig($hookType){
        $collections = $this->configFactory->create()->getCollection();
        $collections = $collections->addFieldToFilter('config_key', $hookType);
        $data = $collections->getData();

        return (count($data) > 0 ? array_values($data)[0] : false);
    }

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ConfigFactory $configFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->configFactory = $configFactory;
    }

    /**
     * Load the page defined in view/adminhtml/layout/commercetemplate_webhook_index.xml
     *
     * @return Page
     */
    public function execute()
    {
        if($this->getRequest()->getMethod() == 'POST'){
            $posts = $this->getRequest()->getPostValue();
            unset($posts['form_key']);
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

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(static::MENU_ID);
        $resultPage->getConfig()->getTitle()->prepend(__('Aichat Webhook'));

        return $resultPage;
    }
}

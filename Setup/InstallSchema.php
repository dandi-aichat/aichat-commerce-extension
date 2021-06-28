<?php
namespace Aichat\CommerceTemplate\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if(!$installer->tableExists('aichat_commercetemplate_config')){
            $table = $installer->getConnection()->newTable(
				$installer->getTable('aichat_commercetemplate_config')
			)
            ->addColumn(
				'config_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				[
					'identity' => true,
					'nullable' => false,
					'primary'  => true,
					'unsigned' => true,
				],
				'Config ID'
			)
            ->addColumn(
				'config_key',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				['nullable' => false],
				'Config Key/Name'
			)
            ->addIndex($installer->getIdxName('aichat_commercetemplate_config', ['config_key']), ['config_key'])
			->addColumn(
				'config_value',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				255,
				[],
				'Config Value'
			)
            ->addColumn(
				'created_at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
				'Created At'
			)->addColumn(
				'updated_at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
				'Updated At')
			->setComment('Config Table');
            $installer->getConnection()->createTable($table);

			$installer->getConnection()->addIndex(
				$installer->getTable('aichat_commercetemplate_config'),
    				$installer->getIdxName(
    					$installer->getTable('aichat_commercetemplate_config'),
    					['config_key'],
    					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
    				),
				['config_key'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
			);
        }

        if(!$installer->tableExists('aichat_commercetemplate_history')){
            $table = $installer->getConnection()
                ->newTable($installer->getTable('aichat_commercetemplate_history'))
                ->addColumn(
                    'log_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                    'Log Id'
                )
                ->addColumn('hook_name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'Hook Name')
                ->addColumn('status', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 64, [], 'Log Status')
                ->addColumn('payload_url', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 512, ['nullable' => false], 'Payload URL')
                ->addColumn('message', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 512, [], 'Message')
                ->addColumn('response', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '2M', [], 'Response')
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Update At'
                )
                ->setComment('History Table');
            $installer->getConnection()->createTable($table);
        }

        if(!$installer->tableExists('aichat_commercetemplate_checkout')){
            $table = $installer->getConnection()->newTable(
				$installer->getTable('aichat_commercetemplate_checkout')
			)
            ->addColumn(
				'id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				[
					'identity' => true,
					'nullable' => false,
					'primary'  => true,
					'unsigned' => true,
				],
				'Table ID'
			)
            ->addColumn(
				'quote_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['nullable' => false],
				'Cart Quote Id'
			)
            ->addColumn(
				'created_at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
				'Created At'
			)->addColumn(
				'updated_at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
				'Updated At')
			->setComment('Checkout History Table');
            $installer->getConnection()->createTable($table);

			$installer->getConnection()->addIndex(
				$installer->getTable('aichat_commercetemplate_checkout'),
    				$installer->getIdxName(
    					$installer->getTable('aichat_commercetemplate_checkout'),
    					['quote_id'],
    					\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
    				),
				['quote_id'],
				\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
			);
        }

        $installer->endSetup();
    }
}

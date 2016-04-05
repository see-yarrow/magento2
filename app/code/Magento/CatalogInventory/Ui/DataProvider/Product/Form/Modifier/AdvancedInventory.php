<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * Data provider for advanced inventory form
 */
class AdvancedInventory extends AbstractModifier
{
    const STOCK_DATA_FIELDS = 'stock_data';

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var array
     */
    private $meta = [];

    /**
     * @param LocatorInterface $locator
     * @param StockRegistryInterface $stockRegistry
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        StockRegistryInterface $stockRegistry,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->stockRegistry = $stockRegistry;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $fieldCode = 'quantity_and_stock_status';

        $model = $this->locator->getProduct();
        $modelId = $model->getId();

        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $modelId,
            $model->getStore()->getWebsiteId()
        );

        $stockData = $modelId ? $this->getData($stockItem) : [];
        if (!empty($stockData)) {
            $data[$modelId][self::DATA_SOURCE_DEFAULT][self::STOCK_DATA_FIELDS] = $stockData;
        }
        if (isset($stockData['is_in_stock'])) {
            $data[$modelId][self::DATA_SOURCE_DEFAULT][$fieldCode]['is_in_stock'] =
                (int)$stockData['is_in_stock'];
        }

        return $data;
    }

    /**
     * Get Stock Data
     *
     * @param StockItemInterface $stockItem
     * @return array
     */
    private function getData(StockItemInterface $stockItem)
    {
        return [
            StockItemInterface::MANAGE_STOCK => $stockItem->getManageStock(),
            StockItemInterface::USE_CONFIG_MANAGE_STOCK => $stockItem->getUseConfigManageStock(),
            StockItemInterface::QTY => $stockItem->getQty(),
            StockItemInterface::MIN_QTY => (float)$stockItem->getMinQty(),
            StockItemInterface::USE_CONFIG_MIN_QTY => $stockItem->getUseConfigMinQty(),
            StockItemInterface::MIN_SALE_QTY => (float)$stockItem->getMinSaleQty(),
            StockItemInterface::USE_CONFIG_MIN_SALE_QTY => $stockItem->getUseConfigMinSaleQty(),
            StockItemInterface::MAX_SALE_QTY => (float)$stockItem->getMaxSaleQty(),
            StockItemInterface::USE_CONFIG_MAX_SALE_QTY => $stockItem->getUseConfigMaxSaleQty(),
            StockItemInterface::IS_QTY_DECIMAL => $stockItem->getIsQtyDecimal(),
            StockItemInterface::IS_DECIMAL_DIVIDED => $stockItem->getIsDecimalDivided(),
            StockItemInterface::BACKORDERS => $stockItem->getBackorders(),
            StockItemInterface::USE_CONFIG_BACKORDERS => $stockItem->getUseConfigBackorders(),
            StockItemInterface::NOTIFY_STOCK_QTY => (float)$stockItem->getNotifyStockQty(),
            StockItemInterface::USE_CONFIG_NOTIFY_STOCK_QTY => $stockItem->getUseConfigNotifyStockQty(),
            StockItemInterface::ENABLE_QTY_INCREMENTS => $stockItem->getEnableQtyIncrements(),
            StockItemInterface::USE_CONFIG_ENABLE_QTY_INC => $stockItem->getUseConfigEnableQtyInc(),
            StockItemInterface::QTY_INCREMENTS => (float)$stockItem->getQtyIncrements(),
            StockItemInterface::USE_CONFIG_QTY_INCREMENTS => $stockItem->getUseConfigQtyIncrements(),
            StockItemInterface::IS_IN_STOCK => $stockItem->getIsInStock(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->prepareMeta();

        return $this->meta;
    }

    /**
     * @return void
     */
    private function prepareMeta()
    {
        $fieldCode = 'quantity_and_stock_status';
        $pathField = $this->arrayManager->findPath($fieldCode, $this->meta, null, 'children');

        if ($pathField) {
            $labelField = $this->arrayManager->get(
                $this->arrayManager->slicePath($pathField, 0, -2) . '/arguments/data/config/label',
                $this->meta
            );
            $fieldsetPath = $this->arrayManager->slicePath($pathField, 0, -4);

            $this->meta = $this->arrayManager->merge(
                $pathField . '/arguments/data/config',
                $this->meta,
                [
                    'label' => __('Stock Status'),
                    'value' => '1',
                    'dataScope' => $fieldCode . '.is_in_stock',
                    'scopeLabel' => '[GLOBAL]',
                ]
            );
            $this->meta = $this->arrayManager->merge(
                $this->arrayManager->slicePath($pathField, 0, -2) . '/arguments/data/config',
                $this->meta,
                [
                    'label' => __('Stock Status'),
                    'scopeLabel' => '[GLOBAL]',
                ]
            );

            $container['arguments']['data']['config'] = [
                'formElement' => 'container',
                'componentType' => 'container',
                'component' => "Magento_Ui/js/form/components/group",
                'label' => $labelField,
                'breakLine' => false,
                'dataScope' => $fieldCode,
                'scopeLabel' => '[GLOBAL]',
                'source' => 'product_details',
                'sortOrder' =>
                    (int) $this->arrayManager->get(
                        $this->arrayManager->slicePath($pathField, 0, -2) . '/arguments/data/config/sortOrder',
                        $this->meta
                    ) - 1,
            ];
            $qty['arguments']['data']['config'] = [
                'component' => 'Magento_CatalogInventory/js/components/qty-validator-changer',
                'dataType' => 'number',
                'formElement' => 'input',
                'componentType' => 'field',
                'visible' => '1',
                'require' => '0',
                'additionalClasses' => 'admin__field-small',
                'dataScope' => 'qty',
                'validation' => [
                    'validate-number' => true,
                    'validate-digits' => true,
                ],
                'imports' => [
                    'handleChanges' => '${$.provider}:data.product.stock_data.is_qty_decimal',
                ],
                'sortOrder' => 10,
            ];
            $advancedInventoryButton['arguments']['data']['config'] = [
                'displayAsLink' => true,
                'formElement' => 'container',
                'componentType' => 'container',
                'component' => 'Magento_Ui/js/form/components/button',
                'template' => 'ui/form/components/button/container',
                'actions' => [
                    [
                        'targetName' => 'product_form.product_form.advanced_inventory_modal',
                        'actionName' => 'toggleModal',
                    ],
                ],
                'title' => __('Advanced Inventory'),
                'provider' => false,
                'additionalForGroup' => true,
                'source' => 'product_details',
                'sortOrder' => 20,
            ];
            $container['children'] = [
                'qty' => $qty,
                'advanced_inventory_button' => $advancedInventoryButton,
            ];

            $this->meta = $this->arrayManager->merge(
                $fieldsetPath . '/children',
                $this->meta,
                ['quantity_and_stock_status_qty' => $container]
            );
        }
    }
}

<?php

/**
 * Class Excellware_Dynamo_Model_Product_Api_V2
 *
 * This file exists to patch Mage_Catalog_Model_Product_Api_V2 instead of making a core code modification.
 *
 * Based off http://blog.rastating.com/adding-additional-attributes-in-magento-v2-api-in-ws-i-compliance-mode/
 *
 * This is supposedly necessary for Excellware's Dynamo integration
 *
 * @author Navarr Barnier <me@navarr.me>
 */
class Excellware_Dynamo_Model_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2
{
    /** @inheritdoc */
    protected function _prepareDataForSave($product, $productData)
    {
        if (!is_object($productData)) {
            $this->_fault('data_invalid');
        }
        if (property_exists($productData, 'website_ids') && is_array($productData->website_ids)) {
            $product->setWebsiteIds($productData->website_ids);
        }

        if (property_exists($productData, 'additional_attributes')) {
            if (property_exists($productData->additional_attributes, 'single_data')) {
                foreach ($productData->additional_attributes->single_data as $_attribute) {
                    $_attrCode = $_attribute->key;
                    $productData->$_attrCode = $_attribute->value;
                }
            }
            if (property_exists($productData->additional_attributes, 'multi_data')) {
                foreach ($productData->additional_attributes->multi_data as $_attribute) {
                    $_attrCode = $_attribute->key;
                    $productData->$_attrCode = $_attribute->value;
                }
            }
            /* BEGIN MODIFICATION */
            if (gettype($productData->additional_attributes) == 'array') {
                foreach ($productData->additional_attributes as $k => $v) {
                    $_attrCode = $k;
                    $productData->{$_attrCode} = $v;
                }
            }
            /* END MODIFICATION */
            unset($productData->additional_attributes);
        }

        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            $_attrCode = $attribute->getAttributeCode();

            //Unset data if object attribute has no value in current store
            if (Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID !== (int) $product->getStoreId()
                && !$product->getExistsStoreValueFlag($_attrCode)
                && !$attribute->isScopeGlobal()
            ) {
                $product->setData($_attrCode, false);
            }

            if ($this->_isAllowedAttribute($attribute) && (isset($productData->$_attrCode))) {
                $product->setData(
                    $_attrCode,
                    $productData->$_attrCode
                );
            }
        }

        if (property_exists($productData, 'categories') && is_array($productData->categories)) {
            $product->setCategoryIds($productData->categories);
        }

        if (property_exists($productData, 'websites') && is_array($productData->websites)) {
            foreach ($productData->websites as &$website) {
                if (is_string($website)) {
                    try {
                        $website = Mage::app()->getWebsite($website)->getId();
                    } catch (Exception $e) { }
                }
            }
            $product->setWebsiteIds($productData->websites);
        }

        if (Mage::app()->isSingleStoreMode()) {
            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }

        if (property_exists($productData, 'stock_data')) {
            $_stockData = array();
            foreach ($productData->stock_data as $key => $value) {
                $_stockData[$key] = $value;
            }
            $product->setStockData($_stockData);
        }

        if (property_exists($productData, 'tier_price')) {
            $tierPrices = Mage::getModel('catalog/product_attribute_tierprice_api_V2')
                              ->prepareTierPrices($product, $productData->tier_price);
            $product->setData(Mage_Catalog_Model_Product_Attribute_Tierprice_Api_V2::ATTRIBUTE_CODE, $tierPrices);
        }
    }
}

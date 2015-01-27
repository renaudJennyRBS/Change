<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Product;

/**
 * @name \Rbs\Catalog\Product\ProductDataComposer
 */
class ProductDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Catalog\Documents\Product
	 */
	protected $product;

	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	/**
	 * @var \Rbs\Catalog\Attribute\AttributeManager
	 */
	protected $attributeManager;

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var \Rbs\Stock\StockManager
	 */
	protected $stockManager;

	/**
	 * @var \Rbs\Review\ReviewManager
	 */
	protected $reviewManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;


	function __construct(\Change\Events\Event $event)
	{
		$this->product = $event->getParam('product');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');
		$this->reviewManager = $genericServices->getReviewManager();

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->stockManager = $commerceServices->getStockManager();
		$this->attributeManager = $commerceServices->getAttributeManager();
	}

	/**
	 * @return integer
	 */
	protected function getWebStoreId()
	{
		return isset($this->data['webStoreId']) ? intval($this->data['webStoreId']) : 0;
	}

	/**
	 * @return integer
	 */
	protected function getBillingAreaId()
	{
		return isset($this->data['billingAreaId']) ? intval($this->data['billingAreaId']) : 0;
	}

	/**
	 * @return integer[]|null
	 */
	protected function getTargetIds()
	{
		return isset($this->data['targetIds']) && is_array($this->data['targetIds']) ? $this->data['targetIds'] : null;
	}

	/**
	 * @return integer
	 */
	protected function getQuantity()
	{
		return isset($this->data['quantity']) ? intval($this->data['quantity']) : 1;
	}

	/**
	 * @return null|string
	 */
	protected function getZone()
	{
		return isset($this->data['zone']) ? strval($this->data['zone']) : null;
	}

	/**
	 * @return \Rbs\Price\Documents\BillingArea|null
	 */
	protected function getBillingArea()
	{
		return $this->documentManager->getDocumentInstance($this->getBillingAreaId());
	}

	/**
	 * @return \Rbs\Store\Documents\WebStore|null
	 */
	protected function getWebStore()
	{
		return $this->documentManager->getDocumentInstance($this->getWebStoreId());
	}

	protected function generateDataSets()
	{
		$this->dataSets = [
			'common' => [
				'id' => $this->product->getId(),
				'LCID' => $this->product->getCurrentLCID(),
				'title' => $this->product->getCurrentLocalization()->getTitle(),
			],
			'cart' => ['hasStock' => false, 'hasPrice' => false,]
		];

		$this->generateCommonDataSet();

		$this->generateAttributesDataSet();

		$this->generateRootProductDataSet();

		if ($this->product->getProductSet())
		{
			$this->dataSets['common']['type'] = 'set';
		}
		elseif ($this->product->getVariantGroup())
		{
			$this->dataSets['common']['type'] = 'variant';
		}
		else
		{
			$this->dataSets['common']['type'] = 'simple';
		}

		$sku = $this->product->getSku();
		if ($sku)
		{
			$this->generateStockDataSet($sku);
			$this->generatePriceDataSet($sku);
		}
		else
		{
			$this->generateWithoutSkuStockAndPriceDataSet();
		}
		$this->generateCartDataSet();

		if ($this->hasDataSet('reviews'))
		{
			$this->dataSets['reviews'] = $this->reviewManager->getDataSetForTarget($this->product->getId(), []);
		}
	}

	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateCommonDataSet()
	{
		$publishedData = new \Change\Http\Ajax\V1\PublishedData($this->product);
		$section = $this->section ? $this->section : $this->website;
		$this->dataSets['common']['URL'] = $publishedData->getURLData($this->URLFormats, $section);
		$visuals = $this->catalogManager->getProductVisuals($this->product);
		foreach ($visuals as $visual)
		{
			$formats = $this->getImageData($visual);
			if ($formats)
			{
				$this->dataSets['common']['visuals'][] = $formats;
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getProductSetContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $this->dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data, 'detailed' => $this->hasDataSet('productSet')];
	}


	/**
	 * @return array
	 */
	protected function getRootProductContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats, 'dataSetNames' => $this->dataSetNames,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data, 'detailed' => true];
	}

	protected function generateRootProductDataSet()
	{
		$product = $this->product;
		if ($this->detailed)
		{
			if ($product->getProductSet())
			{
				$this->dataSets['productSet'] = ['products' => []];
				$context = $this->getProductSetContext();
				foreach ($product->getProductSet()->getProducts() as $productSet)
				{
					$data = $this->catalogManager->getProductData($productSet, $context);
					if (is_array($data) && count($data))
					{
						$this->dataSets['productSet']['products'][] = $data;
					}

				}
			}
			elseif ($product->getVariantGroup())
			{
				$variantGroup = $product->getVariantGroup();
				if ($variantGroup->getRootProduct() === $product)
				{
					/** @var \Rbs\Catalog\Documents\Attribute[] $axesAttributes */
					$axesAttributes = $variantGroup->getAxesAttributes()->toArray();
					$axesData = [];
					$orderedValue = [];
					foreach ($axesAttributes as $index => $axis)
					{
						$axisData = ['id' => strval($axis->getId()), 'defaultItems' => []];
						$axisData['title'] = $axis->getCurrentLocalization()->getTitle();
						$axisData['technicalName'] = $axis->getTechnicalName();
						$defaultItems = $axis->getDefaultItems();
						if (count($defaultItems))
						{
							foreach ($defaultItems as $itemIndex => $item)
							{
								$axisData['defaultItems'][] = ['value' => $item->getValue(), 'title' => $item->getTitle()];
								$orderedValue[$index][$item->getValue()] = str_pad($itemIndex, 3, '0', STR_PAD_LEFT);
							}
						}
						$axesData[] = $axisData;
					}
					$variantProductsData = $this->catalogManager->getVariantProductsData($product);

					if (count($orderedValue)) {
						usort($variantProductsData, function ($vPDA, $vPDB) use ($orderedValue) {
							$axA = $vPDA['axesValues'];
							$axB = $vPDB['axesValues'];
							if ($axA == $axB) {return 0;}
							$cmp = min(count($axA), count($axB));
							for ($i = 0; $i < $cmp; $i++) {
								$ova = isset($orderedValue[$i][$axA[$i]]) ? $orderedValue[$i][$axA[$i]] : $axA[$i];
								$ovb = isset($orderedValue[$i][$axB[$i]]) ? $orderedValue[$i][$axB[$i]] : $axB[$i];
								if ($ova != $ovb) {
									return strcasecmp($ova, $ovb);
								}
							}
							return (count($axA) < count($axB)) ? -1 : 1;
						});
					}

					$productsData = [];
					$nbAxes = count($axesAttributes);
					foreach ($variantProductsData as $info)
					{
						if (!$info['published'])
						{
							continue;
						}
						unset($info['published']);
						if (count($info['axesValues']) == $nbAxes)
						{
							/** @var \Rbs\Catalog\Documents\Product $variantProduct */
							$variantProduct = $this->documentManager->getDocumentInstance($info['id']);
							$sku = $variantProduct->getSku();
							if ($sku instanceof \Rbs\Stock\Documents\Sku)
							{
								/* @var $sku \Rbs\Stock\Documents\Sku */
								$level = $this->stockManager->getInventoryLevel($sku, $this->getWebStoreId());
								$threshold = $this->stockManager->getInventoryThreshold($sku, $this->getWebStoreId(), $level);
								$info['hasStock'] =  $level > 0;
								$info['threshold'] = $threshold;
								$info['allowBackorders'] = $sku->getAllowBackorders();
							}
						}
						$productsData[] = $info;
					}
					$this->dataSets['variants'] = ['axes' => $axesData, 'products' => $productsData];
				}
				else
				{
					if ($this->hasDataSet('rootProduct'))
					{
						$this->dataSets['rootProduct'] = $this->catalogManager->getProductData($variantGroup->getRootProduct(),
							$this->getRootProductContext());
					}
					else
					{
						$this->dataSets['rootProduct'] = ['id' => $variantGroup->getRootProductId()];
					}
				}
			}
		}
		elseif ($product->getVariantGroup())
		{
			$variantGroup = $product->getVariantGroup();
			$rootProduct = $variantGroup->getRootProduct();
			if ($rootProduct !== $product)
			{
				$attributes = $this->getAttributesGroupDataSet($rootProduct, $rootProduct->getAttribute(), null);
				foreach ($attributes as $key => $value)
				{
					if (!isset($this->dataSets['attributes'][$key]['value']))
					{
						$this->dataSets['attributes'][$key] = $value;
					}
				}

				if ($this->hasDataSet('rootProduct'))
				{
					$this->dataSets['rootProduct'] = $this->catalogManager->getProductData($variantGroup->getRootProduct(),
						$this->getRootProductContext());
				}
				else
				{
					$this->dataSets['rootProduct'] = ['id' => $variantGroup->getRootProductId()];
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array|mixed|null
	 */
	protected function getDocumentData($document)
	{
		if ($document instanceof \Rbs\Media\Documents\Image)
		{
			return $this->getImageData($document);
		}
		return $this->getPublishedData($document);
	}

	/**
	 * @param \Rbs\Media\Documents\Image $image
	 * @return array|null
	 */
	protected function getImageData($image)
	{
		$imagesFormats = new \Rbs\Media\Http\Ajax\V1\ImageFormats($image);
		$formats = $imagesFormats->getFormatsData($this->visualFormats);
		return count($formats) ? $formats : null;
	}

	protected function generateAttributesDataSet()
	{
		$product = $this->product;
		$dataSet = $this->getAttributesGroupDataSet($product, $product->getAttribute(), null);
		$this->dataSets['attributes'] = $dataSet;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\Attribute $attributeGroup
	 * @param string $section
	 * @return array
	 */
	protected function getAttributesGroupDataSet($product, $attributeGroup, $section)
	{
		$dataSet = [];
		if ($attributeGroup instanceof \Rbs\Catalog\Documents\Attribute
			&& $attributeGroup->getValueType() == \Rbs\Catalog\Documents\Attribute::TYPE_GROUP
		)
		{
			foreach ($attributeGroup->getAttributes() as $attribute)
			{
				if ($attribute->getValueType() == \Rbs\Catalog\Documents\Attribute::TYPE_GROUP)
				{
					$subSection = $section ? $section : $attribute->getCurrentLocalization()->getTitle();
					$subDataSet = $this->getAttributesGroupDataSet($product, $attribute, $subSection);
					foreach ($subDataSet as $key => $v)
					{
						$dataSet[$key] = $v;
					}
				}
				else
				{
					$visibility = $attribute->getVisibility();
					if (!is_array($visibility))
					{
						$visibility = [];
					}
					if ($this->detailed)
					{
						foreach ($visibility as $name)
						{
							$this->dataSets['attributesVisibility'][$name][] = strval($attribute->getId());
						}
						$attrData = ['section' => $section, 'title' => $attribute->getCurrentLocalization()->getTitle()];
						$desc = $attribute->getCurrentLocalization()->getDescription();
						if ($desc && !$desc->isEmpty())
						{
							$attrData['description'] = $this->formatRichText($desc);
						}
					}
					else
					{
						if (!in_array('listItem', $visibility))
						{
							continue;
						}
						$this->dataSets['attributesVisibility']['listItem'][] = strval($attribute->getId());
						$attrData = [];
					}
					$attrData['value'] = $this->getAttributeDataValue($product, $attribute);
					$attrData['type'] = $this->getAttributeDataType($product, $attribute);
					$dataSet[$attribute->getId()] = $attrData;
				}
			}
		}
		return $dataSet;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @return mixed
	 */
	protected function getAttributeDataType($product, $attribute)
	{
		$type = $attribute->getValueType();
		if ($type == \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
		{
			$property = $attribute->getModelProperty();
			if ($property)
			{
				$type = $property->getType();
			}
		}
		if ($type == 'DocumentId') {
			$type = 'Document';
		} elseif ($type == 'DocumentIdArray') {
			$type = 'DocumentArray';
		} elseif ($type == 'Text') {
			$type = 'RichText';
		}
		return $type;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @return mixed
	 */
	protected function getAttributeDataValue($product, $attribute)
	{
		$value = $attribute->getValue($product);
		if ($attribute->getTechnicalName())
		{
			$this->dataSets['common']['attributes'][$attribute->getTechnicalName()] = strval($attribute->getId());
		}
		if ($value === null)
		{
			return null;
		}
		switch ($attribute->getValueType())
		{
			case \Rbs\Catalog\Documents\Attribute::TYPE_TEXT:
				$value = new \Change\Documents\RichtextProperty($value);
				if (!$value->isEmpty())
				{
					return $this->formatRichText($value);
				}
				return null;
			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTID:
				$value = $this->documentManager->getDocumentInstance($value);
				if ($value)
				{
					return $this->getDocumentData($value);
				}
				return null;
			case \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY:
				$property = $attribute->getModelProperty();
				if ($property)
				{
					if ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENTID)
					{
						$value = $this->documentManager->getDocumentInstance($value);
					}
					$this->dataSets['common']['attributes'][$property->getName()] = strval($attribute->getId());
				}
				break;
			case \Rbs\Catalog\Documents\Attribute::TYPE_DOCUMENTIDARRAY:
				if (is_array($value) && count($value))
				{
					$values = [];
					foreach ($value as $id)
					{
						$v = $this->getDocumentData($this->documentManager->getDocumentInstance($id));
						if ($v !== null)
						{
							$values[] = $v;
						}
					}
					return count($values) ? $values : null;
				}
				return null;
		}

		if ($value instanceof \Change\Documents\AbstractDocument)
		{
			return $this->getDocumentData($value);
		}
		elseif ($value instanceof \Change\Documents\DocumentArrayProperty)
		{
			$values = [];
			foreach ($value as $v)
			{
				$v = $this->getDocumentData($v);
				if ($v !== null)
				{
					$values[] = $v;
				}
			}
			return count($values) ? $values : null;
		}
		elseif ($value instanceof \Change\Documents\RichtextProperty)
		{
			if (!$value->isEmpty())
			{
				return $this->formatRichText($value);
			}
			return null;
		}
		elseif ($value instanceof \DateTime)
		{
			return $this->formatDate($value);
		}
		elseif (is_object($value))
		{
			$callable = [$value, 'toArray'];
			if (is_callable($callable)) {
				return call_user_func($callable);
			}
			return null;
		}
		elseif (is_bool($value))
		{
			$key = 'c.types.' . ($value ? 'yes' : 'no');
			return $this->i18nManager->trans($key, ['ucf']);
		}
		elseif (is_float($value))
		{
			$nf = new \NumberFormatter($this->i18nManager->getLCID(), \NumberFormatter::DECIMAL);
			return  $nf->format($value);
		}
		return $value;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 */
	protected function generateStockDataSet($sku)
	{
		$this->dataSets['stock']['sku'] = $sku->getCode();
		if ($this->detailed && $sku->getPartNumber())
		{
			$this->dataSets['common']['reference'] = $sku->getPartNumber();
		}
		$this->dataSets['stock']['sku'] = $sku->getCode();
		$level = $this->stockManager->getInventoryLevel($sku, $this->getWebStoreId());
		$this->dataSets['cart']['hasStock'] = ($level > 0) || $sku->getAllowBackorders();
		$this->dataSets['cart']['quantityIncrement'] = max(1,  $sku->getQuantityIncrement());

		$this->dataSets['stock']['threshold'] = $this->stockManager->getInventoryThreshold($sku, $this->getWebStoreId(), $level);
		$this->dataSets['stock']['thresholdTitle'] = $this->stockManager->getInventoryThresholdTitle($this->dataSets['stock']['threshold']);
		if ($this->dataSets['cart']['hasStock'])
		{
			$this->dataSets['cart']['minQuantity'] = $sku->getMinQuantity();
			$this->dataSets['cart']['maxQuantity'] = $sku->getMaxQuantity() ? $sku->getMaxQuantity() :\Rbs\Stock\StockManager::UNLIMITED_LEVEL;
		}
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 */
	protected function generatePriceDataSet($sku)
	{
		$priceManager = $this->priceManager;
		$billingArea = $this->getBillingArea();
		$webStoreId = $this->getWebStoreId();
		if ($billingArea && $webStoreId)
		{
			$price = $priceManager->getPriceBySku($sku, ['webStore' => $webStoreId, 'billingArea' => $billingArea,
				'targetIds' => $this->getTargetIds()]);

			if ($price && $price->getValue() !== null)
			{
				$this->dataSets['cart']['hasPrice'] = true;
				$this->fillPriceDataSetWithPrice($price);
			}
		}
	}

	protected function generateCartDataSet()
	{
		$cartDataSet = isset($this->dataSets['cart']) ? $this->dataSets['cart'] : [];
		$cartDataSet += ['hasStock' => false, 'hasPrice' => false];
		if ($cartDataSet['hasStock'] && $cartDataSet['hasPrice'])
		{
			$cartDataSet['key'] = strval($this->product->getId());
		}
		$this->dataSets['cart'] = $cartDataSet;
	}

	protected function generateWithoutSkuStockAndPriceDataSet()
	{
		$webStore = $this->getWebStore();
		$skuArray = $this->catalogManager->getAllSku($this->product, true);
		if (count($skuArray))
		{
			$threshold = $this->stockManager->getInventoryThresholdForManySku($skuArray, $webStore);
			$this->dataSets['stock']['threshold'] = $threshold;
			$thresholdTitle = $this->stockManager->getInventoryThresholdTitle($threshold);
			$this->dataSets['stock']['thresholdTitle'] = $thresholdTitle;

			$billingArea = $this->getBillingArea();
			if ($billingArea && $webStore)
			{
				$options = ['webStore' => $webStore, 'billingArea' => $billingArea, 'targetIds' => $this->getTargetIds()];

				/** @var \Rbs\Price\PriceInterface $lowestPrice */
				$lowestPrice = null;
				$lowestPriceValue = null;
				$hasDifferentPrices = false;
				$priceManager = $this->priceManager;
				foreach ($skuArray as $sku)
				{
					$price = $priceManager->getPriceBySku($sku, $options);
					if ($price != null)
					{
						$value = $price->getValue();
						if ($lowestPrice == null)
						{
							$lowestPrice = $price;
							$lowestPriceValue = $value;
						}
						elseif ($value != $lowestPriceValue)
						{
							$hasDifferentPrices = true;
							if ($value < $lowestPriceValue)
							{
								$lowestPrice = $price;
								$lowestPriceValue = $value;
							}
						}
					}
				}

				if ($lowestPrice)
				{
					$this->dataSets['price']['hasDifferentPrices'] = $hasDifferentPrices;
					$this->fillPriceDataSetWithPrice($lowestPrice);
				}
			}
		}
	}

	/**
	 * @param \Rbs\Price\PriceInterface $price
	 */
	protected function fillPriceDataSetWithPrice($price)
	{
		$priceManager = $this->priceManager;
		$billingArea = $this->getBillingArea();

		$currencyCode = $billingArea->getCurrencyCode();
		$precision = $priceManager->getRoundPrecisionByCurrencyCode($currencyCode);
		$this->dataSets['price']['currencyCode'] = $currencyCode;
		$this->dataSets['price']['precision'] = $precision;

		$taxesApplication = null;
		$valueWithTax = $valueWithoutTax = $baseValueWithTax = $baseValueWithoutTax = null;
		$value = $price->getValue();
		$baseValue = $price->getBasePriceValue();
		$zone = $this->getZone();

		$options = $price->getOptions()->toArray();
		if (count($options)) {
			$this->dataSets['price']['options'] = $options;
		}

		if ($zone)
		{
			/** @var \Rbs\Price\Documents\Tax[] $taxes */
			$taxes = $billingArea->getTaxes()->toArray();
			$taxesApplication = $priceManager->getTaxesApplication($price, $taxes, $zone, $currencyCode, $this->getQuantity());
			$basedAmountTaxesApplication = [];
			$baseValueWithTax = $baseValueWithoutTax = $baseValue;
			if (count($taxesApplication))
			{
				if ($baseValue !== null)
				{
					$rate = $baseValue / $value;
					foreach ($taxesApplication as $tax)
					{
						$basedAmountTax = clone($tax);
						$basedAmountTax->setValue($tax->getValue() * $rate);
						$basedAmountTaxesApplication[] = $basedAmountTax;
					}
				}
			}

			if ($price->isWithTax())
			{
				$valueWithTax = $value;
				$valueWithoutTax = $priceManager->getValueWithoutTax($valueWithTax, $taxesApplication);
				if ($baseValueWithTax !== null)
				{
					$baseValueWithoutTax = $priceManager->getValueWithoutTax($baseValueWithTax, $basedAmountTaxesApplication);
				}
			}
			else
			{
				$valueWithoutTax = $value;
				$valueWithTax = $priceManager->getValueWithTax($valueWithoutTax, $taxesApplication);
				if ($baseValue !== null)
				{
					$baseValueWithTax = $priceManager->getValueWithTax($baseValueWithoutTax, $basedAmountTaxesApplication);
				}
			}
		}
		else
		{
			if ($price->isWithTax())
			{
				$valueWithTax = $value;
				$baseValueWithTax = $baseValue;
			}
			else
			{
				$valueWithoutTax = $value;
				$baseValueWithoutTax = $baseValue;
			}
		}

		$this->dataSets['price']['valueWithTax'] = $priceManager->roundValue($valueWithTax, $precision);
		$this->dataSets['price']['valueWithoutTax'] = $priceManager->roundValue($valueWithoutTax, $precision);

		$this->dataSets['price']['baseValueWithTax'] = $priceManager->roundValue($baseValueWithTax, $precision);
		$this->dataSets['price']['baseValueWithoutTax'] = $priceManager->roundValue($baseValueWithoutTax, $precision);

		if (is_array($taxesApplication))
		{
			$this->dataSets['price']['taxes'] = array_map(function (\Rbs\Price\Tax\TaxApplication $ta)
			{
				return $ta->toArray();
			}, $taxesApplication);
		}
		else
		{
			$this->dataSets['price']['taxes'] = null;
		}
	}
}
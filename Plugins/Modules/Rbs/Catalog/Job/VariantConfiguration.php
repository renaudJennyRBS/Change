<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Job;

use Rbs\Catalog\Product\AxisConfiguration;

/**
 * @name \Rbs\Catalog\Job\VariantConfiguration
 */
class VariantConfiguration
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();
		$variantGroup = $documentManager->getDocumentInstance($job->getArgument('variantGroupId'));
		if (!($variantGroup instanceof \Rbs\Catalog\Documents\VariantGroup))
		{
			$event->setResultArgument('inputArgument', 'Variant group not found');
			$event->success();
			return;
		}

		if ($variantGroup->getMeta('Job_VariantConfiguration') != $job->getId())
		{
			$event->setResultArgument('inputArgument', 'Not current Job');
			$event->success();
			return;
		}

		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			$event->failed('Commerce services not set');
			return;
		}

		$tm = $applicationServices->getTransactionManager();
		$attributeManager = $commerceServices->getAttributeManager();

		try
		{
			$tm->begin();
			$productsConfiguration = $job->getArgument('products');

			$this->updateVariantProducts($attributeManager, $documentManager, $variantGroup, $productsConfiguration);

			$variantGroup->setMeta('Job_VariantConfiguration', null);
			$variantGroup->saveMetas();
			$event->success();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$event->failed($e->getMessage());
			$tm->rollBack();
		}
	}

	/**
	 * @param \Rbs\Catalog\Attribute\AttributeManager $attributeManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param array $productsConfiguration
	 */
	public function updateVariantProducts($attributeManager, $documentManager, $variantGroup, $productsConfiguration)
	{
		$axesConfiguration = $this->buildAxesConfiguration($documentManager, $variantGroup);
		$nbAxes = count($axesConfiguration);
		if ($nbAxes == 0)
		{
			$this->deleteUnusedProduct($variantGroup->getVariantProducts(), $variantGroup->getNewSkuOnCreation());
		}
		else
		{
			$fullyQualifiedAxes = $this->extractFullyQualifiedAxes($axesConfiguration, $productsConfiguration);
			if (count($fullyQualifiedAxes) === 0)
			{
				$this->deleteUnusedProduct($variantGroup->getVariantProducts(), $variantGroup->getNewSkuOnCreation());
			}
			else
			{
				if ($variantGroup->getGroupAttribute() === null)
				{
					(new AxesConfiguration())->updateGroupAttribute($attributeManager, $documentManager, $variantGroup);
					$variantGroup->update();
				}

				$loadedProducts = $this->loadVariantProducts($attributeManager, $axesConfiguration, $variantGroup);
				$this->updateFinalVariantProducts($attributeManager, $documentManager, $variantGroup, $axesConfiguration,
					$loadedProducts, $fullyQualifiedAxes);

				$this->updateVirtualVariantProducts($attributeManager, $documentManager, $variantGroup, $axesConfiguration,
					$loadedProducts, $fullyQualifiedAxes);
				$this->deleteUnusedProduct($loadedProducts['products'], $variantGroup->getNewSkuOnCreation());
			}
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product[] $products
	 * @param boolean $deleteSku
	 */
	public function deleteUnusedProduct($products, $deleteSku)
	{
		foreach ($products as $product)
		{
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				if ($deleteSku && $product->getSku())
				{
					$product->getSku()->delete();
				}
				$product->delete();
			}
		}
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @return AxisConfiguration[]
	 */
	public function buildAxesConfiguration(\Change\Documents\DocumentManager $documentManager, \Rbs\Catalog\Documents\VariantGroup $variantGroup)
	{
		/** @var AxisConfiguration[] TYPE_NAME */
		$axesConfiguration = array();

		$arrayConfiguration = $variantGroup->getAxesConfiguration();
		if (is_array($arrayConfiguration))
		{
			foreach ($arrayConfiguration as $data)
			{
				if (is_array($data))
				{
					$conf = new AxisConfiguration();
					$conf->fromArray($data);
					$attribute = $documentManager->getDocumentInstance($conf->getId());
					if ($attribute instanceof \Rbs\Catalog\Documents\Attribute && $attribute->getAxis())
					{
						$conf->setAttribute($attribute);
						$axesConfiguration[] = $conf;
					}
				}
			}
			return $axesConfiguration;
		}

		$nbAxes = count($axesConfiguration);
		if ($nbAxes)
		{
			/** @var $lastAxisConfiguration AxisConfiguration */
			$lastAxisConfiguration = $axesConfiguration[$nbAxes - 1];
			$lastAxisConfiguration->setUrl(true);
		}
		return $axesConfiguration;
	}

	/**
	 * @param AxisConfiguration[] $axesConfiguration
	 * @param array $values [['id'=>integer, 'value'=>mixed], ...]
	 * @return array [mixed, ...]
	 */
	public function flattenValues($axesConfiguration, $values)
	{
		$flatValues = array_fill(0, count($axesConfiguration), null);
		foreach ($axesConfiguration as $index => $axisConfiguration)
		{
			$hasNull = true;
			foreach ($values as $v)
			{
				if ($v['id'] == $axisConfiguration->getId())
				{
					if (isset($v['value']))
					{
						$hasNull = false;
						$flatValues[$index] = $v['value'];
					}
					break;
				}
			}
			if ($hasNull)
			{
				break;
			}
		}
		return $flatValues;
	}

	/**
	 * @param array $flatValues
	 * @return boolean
	 */
	public function isFullyQualified($flatValues)
	{
		foreach ($flatValues as $flatValue)
		{
			if (is_null($flatValue))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * @param AxisConfiguration[] $axesConfiguration
	 * @param array $flatValues
	 * @return array
	 */
	public function normalizeValues($axesConfiguration, $flatValues)
	{
		$values = array();
		foreach ($axesConfiguration as $index => $axisConfiguration)
		{
			$values[] = ['id' => $axisConfiguration->getId(), 'value' => $flatValues[$index]];
		}
		return $values;
	}

	/**
	 * @param $axesConfiguration
	 * @param $productsConfiguration
	 * @return array
	 */
	public function extractFullyQualifiedAxes($axesConfiguration, $productsConfiguration)
	{
		$fullyQualifiedAxes = array();
		if (is_array($productsConfiguration))
		{
			foreach ($productsConfiguration as $productConfiguration)
			{
				if (isset($productConfiguration['values']) && is_array($productConfiguration['values']))
				{
					$values = $productConfiguration['values'];
					$flatValue = $this->flattenValues($axesConfiguration, $values);
					if ($this->isFullyQualified($flatValue))
					{
						$fullyQualifiedAxes[] = $flatValue;
					}
				}
			}
			return $fullyQualifiedAxes;
		}
		return $fullyQualifiedAxes;
	}

	/**
	 * @param \Rbs\Catalog\Attribute\AttributeManager $attributeManager
	 * @param AxisConfiguration[] $axesConfiguration
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @return array ['products' => [...], 'axes' => [...]]
	 */
	public function loadVariantProducts($attributeManager, $axesConfiguration, $variantGroup)
	{
		$loadedProducts = ['products' => [], 'axes' =>[]];
		$axesAttributes = [];
		foreach ($axesConfiguration as $axisConfiguration)
		{
			$axesAttributes[] = $axisConfiguration->getAttribute();
		}

		foreach ($variantGroup->getVariantProducts() as $product)
		{
			$values = $attributeManager->getProductAxesValue($product, $axesAttributes);
			$loadedProducts['products'][] = $product;
			$loadedProducts['axes'][] = $this->flattenValues($axesConfiguration, $values);
		}
		return $loadedProducts;
	}

	/**
	 * @param array $loadedProducts
	 * @param array $flatValues
	 * @return \Rbs\Catalog\Documents\Product|null
	 */
	public function findVariantProduct($loadedProducts, $flatValues)
	{
		foreach ($loadedProducts['axes'] as $index => $checkValue)
		{
			if ($checkValue == $flatValues)
			{
				return $loadedProducts['products'][$index];
			}
		}
		return null;
	}

	/**
	 * @param array $loadedProducts
	 * @param \Rbs\Catalog\Documents\Product $variantProduct
	 * @return $this
	 */
	public function removeVariantProduct(&$loadedProducts, $variantProduct)
	{
		foreach ($loadedProducts['products'] as $index => $checkValue)
		{
			if ($checkValue == $variantProduct)
			{
				$loadedProducts['products'][$index] = null;
			}
		}
		return $this;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param boolean $newSkuOnCreation
	 * @return \Rbs\Catalog\Documents\Product
	 */
	protected function getNewVariantProduct($documentManager, $variantGroup, $newSkuOnCreation = false)
	{
		/** @var $product \Rbs\Catalog\Documents\Product */
		$product = $documentManager->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		$product->setLabel($variantGroup->getLabel());
		$product->setVariantGroup($variantGroup);
		$product->setVariant(true);
		$product->setAttribute($variantGroup->getGroupAttribute());
		$product->setNewSkuOnCreation($newSkuOnCreation);
		return $product;
	}

	/**
	 * @param \Rbs\Catalog\Attribute\AttributeManager $attributeManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param AxisConfiguration[] $axesConfiguration
	 * @param array $loadedProducts
	 * @param array $fullyQualifiedAxes
	 */
	public function updateVirtualVariantProducts($attributeManager, $documentManager, $variantGroup, $axesConfiguration,
		&$loadedProducts, $fullyQualifiedAxes)
	{

		$nbAxes = count($axesConfiguration);
		/** @var $lastAxisConfiguration AxisConfiguration */
		$lastAxisConfiguration = $axesConfiguration[$nbAxes - 1];

		$axesAttributes = [];
		foreach ($axesConfiguration as $axisConfiguration)
		{
			$axesAttributes[] = $axisConfiguration->getAttribute();
		}

		foreach ($axesConfiguration as $index => $axisConfiguration)
		{
			if ($axisConfiguration === $lastAxisConfiguration)
			{
				break;
			}

			if ($axisConfiguration->getUrl())
			{
				$checkedKeys = [];
				foreach ($fullyQualifiedAxes as $flatValues)
				{
					$values = array_slice($flatValues, 0, $index + 1);
					$key = implode('|', $values);
					if (isset($checkedKeys[$key]))
					{
						continue;
					}
					$checkedKeys[$key] = true;
					$flatValues = array_pad($values, $nbAxes, null);
					$product = $this->findVariantProduct($loadedProducts, $flatValues);
					if ($product !== null)
					{
						$product->setCategorizable($axisConfiguration->getCategorizable());
						$product->save();
						$this->removeVariantProduct($loadedProducts, $product);
					}
					else
					{
						$product = $this->getNewVariantProduct($documentManager, $variantGroup, false);
						$product->setLabel($product->getLabel() . '-' . implode('-', $values));
						$product->getCurrentLocalization()->setTitle($product->getLabel());
						$product->setCategorizable($axisConfiguration->getCategorizable());
						$attributeManager->setProductAxesValue($product, $axesAttributes,
							$this->normalizeValues($axesConfiguration, $flatValues));
						$product->create();
					}
				}
			}
		}
	}

	/**
	 * @param \Rbs\Catalog\Attribute\AttributeManager $attributeManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param AxisConfiguration[] $axesConfiguration
	 * @param array $loadedProducts
	 * @param array $fullyQualifiedAxes
	 */
	public function updateFinalVariantProducts($attributeManager, $documentManager, $variantGroup, $axesConfiguration,
		&$loadedProducts, $fullyQualifiedAxes)
	{
		$nbAxes = count($axesConfiguration);

		/** @var $lastAxisConfiguration AxisConfiguration */
		$lastAxisConfiguration = $axesConfiguration[$nbAxes - 1];
		$axesAttributes = [];
		foreach ($axesConfiguration as $axisConfiguration)
		{
			$axesAttributes[] = $axisConfiguration->getAttribute();
		}

		$checkedKeys = [];
		foreach ($fullyQualifiedAxes as $flatValues)
		{
			$key = implode('|', $flatValues);
			if (isset($checkedKeys[$key]))
			{
				continue;
			}

			$checkedKeys[$key] = true;
			$product = $this->findVariantProduct($loadedProducts, $flatValues);
			if ($product !== null)
			{
				$product->setCategorizable($lastAxisConfiguration->getCategorizable());
				$product->save();
				$this->removeVariantProduct($loadedProducts, $product);
			}
			else
			{
				$product = $this->getNewVariantProduct($documentManager, $variantGroup, $variantGroup->getNewSkuOnCreation());
				$product->setLabel($product->getLabel() . '-' . implode('-', $flatValues));
				$product->getCurrentLocalization()->setTitle($product->getLabel());
				$product->setCategorizable($lastAxisConfiguration->getCategorizable());
				$attributeManager->setProductAxesValue($product, $axesAttributes,
					$this->normalizeValues($axesConfiguration, $flatValues));
				$product->create();
			}
		}
	}
} 
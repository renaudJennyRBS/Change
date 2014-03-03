<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Job;

/**
 * @name \Rbs\Catalog\Job\AxesConfiguration
 */
class AxesConfiguration
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

		if ($variantGroup->getMeta('Job_AxesConfiguration') != $job->getId())
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

		$documentManager = $applicationServices->getDocumentManager();
		try
		{
			$tm->begin();
			if ($this->updateGroupAttribute($attributeManager, $documentManager, $variantGroup))
			{
				$variantGroup->update();
			}
			$variantGroup->setMeta('Job_AxesConfiguration', null);
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
	 * @return boolean
	 */
	public function updateGroupAttribute($attributeManager, $documentManager, $variantGroup)
	{
		$groupAttribute = $variantGroup->getGroupAttribute();

		/** @var $attributes \Rbs\Catalog\Documents\Attribute[] */
		$attributes = array();
		foreach ($variantGroup->getAxesAttributes() as $attribute)
		{
			$attributes[] = $attribute;
		}
		foreach ($variantGroup->getOthersAttributes() as $attribute)
		{
			$attributes[] = $attribute;
		}

		if ($groupAttribute === null)
		{
			/** @var $groupAttribute \Rbs\Catalog\Documents\Attribute */
			$groupAttribute = $documentManager->getNewDocumentInstanceByModelName('Rbs_Catalog_Attribute');
			$groupAttribute->setAxis(true);
			$groupAttribute->setLabel($variantGroup->getLabel());
			$groupAttribute->getCurrentLocalization()->setTitle($variantGroup->getLabel());

			$groupAttribute->setValueType(\Rbs\Catalog\Documents\Attribute::TYPE_GROUP);
			foreach ($attributes as $attribute)
			{
				$groupAttribute->getAttributes()->add($attribute);
			}
			$groupAttribute->setVisibility(null);
			$groupAttribute->create();
			$variantGroup->setGroupAttribute($groupAttribute);
			return true;
		}
		else
		{
			$groupAttribute->getAttributes()->fromArray($attributes);
			if ($groupAttribute->isPropertyModified('attributes'))
			{
				$groupAttribute->update();
			}

			$variantConfiguration = new VariantConfiguration();
			$axesConfiguration = $variantConfiguration->buildAxesConfiguration($documentManager, $variantGroup);
			$loadedProducts = $variantConfiguration->loadVariantProducts($attributeManager, $axesConfiguration, $variantGroup);
			if (count($loadedProducts['products']))
			{
				if (count($axesConfiguration))
				{
					$maskValues = array_fill(0, count($axesConfiguration), null);
					foreach ($axesConfiguration as $index => $axisConfiguration)
					{
						$maskValues[$index] = true;
						$products = $this->findProductByMask($loadedProducts, $maskValues);
						foreach ($products as $product)
						{
							if ($axisConfiguration->getUrl())
							{
								$variantConfiguration->removeVariantProduct($loadedProducts, $product);
								$product->setCategorizable($axisConfiguration->getCategorizable());
								$product->setAttribute($groupAttribute);
								$product->update();
							}
						}
					}
					$variantConfiguration->deleteUnusedProduct($loadedProducts['products'], $variantGroup->getNewSkuOnCreation());
				}
			}
			return false;
		}
	}

	/**
	 * @param array $loadedProducts
	 * @param array $maskValues
	 * @return \Rbs\Catalog\Documents\Product[]
	 */
	public function findProductByMask($loadedProducts, $maskValues)
	{
		$products = [];
		foreach ($loadedProducts['axes'] as $index => $axesValues)
		{
			if ($this->checkMask($maskValues, $axesValues))
			{
				$product = $loadedProducts['products'][$index];
				if ($product)
				{
					$products[] = $product;
				}
			}
		}
		return $products;
	}

	/**
	 * @param array $maskValues [true, true, null ...]
	 * @param array $axesValues
	 * @return bool
	 */
	public function checkMask($maskValues, $axesValues)
	{
		foreach ($maskValues as $index => $mask)
		{
			if ($mask === true)
			{
				if (!isset($axesValues[$index]))
				{
					return false;
				}
			}
			elseif ($mask === null)
			{
				if (isset($axesValues[$index]))
				{
					return false;
				}
			}
			elseif ($mask !== $axesValues[$index])
			{
				return false;
			}
		}
		return true;
	}
}
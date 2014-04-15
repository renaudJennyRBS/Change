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
 * @name \Rbs\Catalog\Job\AttributeRefreshValues
 */
class AttributeRefreshValues
{
	public function execute(\Change\Job\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			$event->failed('Commerce services not set');
			return;
		}

		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$attributeId = $job->getArgument('attributeId');
		$attribute = $applicationServices->getDocumentManager()->getDocumentInstance($attributeId);
		if  ($attribute instanceof \Rbs\Catalog\Documents\Attribute 
			&& $attribute->getValueType() == \Rbs\Catalog\Documents\Attribute::TYPE_GROUP && $attribute->getAttributesCount())
		{
			$dm =  $applicationServices->getDocumentManager();
			$groupAttributeIds = [$attribute->getId()];

			foreach ($this->getAncestors($attribute, $dm) as $attr)
			{
				$groupAttributeIds[] = $attr->getId();
			};

			$attributeWithValueIds = [];
			foreach ($this->getAttributesWithValue($attribute) as $attr)
			{
				$attributeWithValueIds[] = $attr->getId();
			};
			
			if (count($groupAttributeIds) && count($attributeWithValueIds))
			{
				$attributeManager = $commerceServices->getAttributeManager();
				$transactionManager = $applicationServices->getTransactionManager();
				$dm->reset();
				foreach ($groupAttributeIds as $groupAttributeId)
				{
					$productIds = $this->getProductIds($groupAttributeId, $dm);
					if (count($productIds)) 
					{
						foreach (array_chunk($productIds, 10) as $chunkProductIds)
						{
							$this->initProductsAttributesValue($chunkProductIds, $attributeWithValueIds, $attributeManager, $transactionManager);
						}
					}
				}
			}
		}
	}

	/**
	 * @param integer[] $productIds
	 * @param integer[] $attributeWithValueIds
	 * @param \Rbs\Catalog\Attribute\AttributeManager $attributeManager
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return null|string
	 */
	protected function initProductsAttributesValue($productIds, $attributeWithValueIds, $attributeManager, $transactionManager)
	{
		try
		{
			$transactionManager->begin();
			foreach ($productIds as $productId)
			{
				$inserted = $attributeManager->initProductAttributesValue($productId, $attributeWithValueIds);
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$transactionManager->rollBack($e);
			return $e->getMessage();
		}
		return null;
	}


	/**
	 * @param integer $attributeId
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return integer[]
	 */
	protected function getProductIds($attributeId, \Change\Documents\DocumentManager $documentManager)
	{
		$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
		$query->andPredicates($query->eq('attribute', $attributeId ));
		return $query->getDocuments()->ids();
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Rbs\Catalog\Documents\Attribute[]
	 */
	protected function getAncestors(\Rbs\Catalog\Documents\Attribute $attribute, \Change\Documents\DocumentManager $documentManager)
	{
		$ancestors = [];
		$query = $documentManager->getNewQuery($attribute->getDocumentModel());
		$query->andPredicates($query->eq('attributes', $attribute));
		foreach ($query->getDocuments() as $parentAttribute)
		{
			if ($parentAttribute instanceof \Rbs\Catalog\Documents\Attribute
				&& $parentAttribute->getValueType() == \Rbs\Catalog\Documents\Attribute::TYPE_GROUP)
			{
				if (!isset($ancestors[$parentAttribute->getId()]))
				{
					$ancestors[$parentAttribute->getId()] = $parentAttribute;
					foreach ($this->getAncestors($parentAttribute, $documentManager) as $ancestorAttribute)
					{
						$ancestors[$ancestorAttribute->getId()] = $ancestorAttribute;
					}
				}
			}
		}
		return array_values($ancestors);
	}

	/**
	 * @param \Rbs\Catalog\Documents\Attribute $attribute
	 * @return \Rbs\Catalog\Documents\Attribute[]
	 */
	protected function getAttributesWithValue(\Rbs\Catalog\Documents\Attribute $attribute)
	{
		$attributesWithValue = [];
		foreach ($attribute->getAttributes() as $childAttribute)
		{
			if ($childAttribute instanceof \Rbs\Catalog\Documents\Attribute)
			{
				if ($childAttribute->getValueType() == \Rbs\Catalog\Documents\Attribute::TYPE_GROUP) 
				{
					foreach ($this->getAttributesWithValue($childAttribute) as $attr)
					{
						$attributesWithValue[$attr->getId()] = $attr;
					}
				}
				elseif ($childAttribute->getValueType() != \Rbs\Catalog\Documents\Attribute::TYPE_PROPERTY)
				{
					$attributesWithValue[$childAttribute->getId()] = $childAttribute;
				}
			}
		}
		return array_values($attributesWithValue);
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Events;

use Change\Db\Query;
use Change\Documents\Property;
use Rbs\Catalog\Documents\Attribute;

/**
* @name \Rbs\Catalog\Events\ModelManager
*/
class ModelManager
{
	public function getFiltersDefinition(\Change\Events\Event $event)
	{
		$model = $event->getParam('model');
		$filtersDefinition = $event->getParam('filtersDefinition');
		if ($model instanceof \Change\Documents\AbstractModel && is_array($filtersDefinition) && $model->isInstanceOf('Rbs_Catalog_Product'))
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$f = ['ucf'];
			$definition = ['name' => 'stockInventory',
				'directiveName' => 'rbs-document-filter-stock-inventory',
				'parameters' => ['restriction' => 'stockInventory'],
				'config' => [
					'listLabel' => $i18nManager->trans('m.rbs.catalog.admin.find_stock_inventory', $f),
					'group' => $i18nManager->trans('m.rbs.admin.admin.common_filter_group', $f)]];
			$filtersDefinition[] = $definition;

			$definition = ['name' => 'productCodes',
				'directiveName' => 'rbs-document-filter-product-codes',
				'parameters' => ['restriction' => 'productCodes'],
				'config' => [
					'listLabel' => $i18nManager->trans('m.rbs.catalog.admin.find_product_codes', $f),
					'group' => $i18nManager->trans('m.rbs.admin.admin.common_filter_group', $f)]];
			$filtersDefinition[] = $definition;

			$filtersDefinition = array_merge($filtersDefinition, $this->getAttributeFilterDefinition($event));

			$event->setParam('filtersDefinition', $filtersDefinition);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return array
	 */
	protected function getAttributeFilterDefinition(\Change\Events\Event $event)
	{
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_Attribute');
		$query->andPredicates($query->in('valueType', [Attribute::TYPE_BOOLEAN, Attribute::TYPE_INTEGER,
			Attribute::TYPE_FLOAT, Attribute::TYPE_DATETIME, Attribute::TYPE_DATETIME, Attribute::TYPE_STRING]));
		$attributes = $query->addOrder('label')->getDocuments();

		$attributeDefinitions = array();
		if (count($attributes))
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$f = ['ucf'];

			/** @var $attribute Attribute */
			foreach ($attributes as $attribute)
			{
				$definition = ['name' => 'attribute' . $attribute->getId(),
					'directiveName' => 'rbs-document-filter-product-attribute',
					'parameters' => ['restriction' => 'attributeValue', 'attributeId' => $attribute->getId()],
					'config' => [
						'listLabel' => $i18nManager->trans('m.rbs.catalog.admin.filter_attribute_list', $f, ['ATTRIBUTENAME' => $attribute->getLabel()]),
						'group' => $i18nManager->trans('m.rbs.admin.admin.common_filter_group', $f),
						'valueType' => $attribute->getValueType(),
						'label' => $i18nManager->trans('m.rbs.catalog.admin.filter_attribute_label', $f, ['ATTRIBUTENAME' => $attribute->getLabel()]),
					]
				];

				if ($attribute->getCollectionCode())
				{
					$collection = $event->getApplicationServices()->getCollectionManager()->getCollection($attribute->getCollectionCode());
					if ($collection)
					{
						$values = array();
						foreach($collection->getItems() as $item)
						{
							$values[] = array('value' => $item->getValue(), 'label' => $item->getLabel());
						}
						if (count($values))
						{
							$definition['config']['possibleValues'] = $values;
						}
					}
				}
				$attributeDefinitions[] = $definition;
			}
		}
		return $attributeDefinitions;
	}

	public function getRestriction(\Change\Events\Event $event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'];
			$restriction = isset($parameters['restriction']) ? $parameters['restriction'] : null;
			if ($restriction === 'stockInventory')
			{
				$parameters = $parameters + ['operator' => null, 'level' => null];
				$operator = $parameters['operator'];
				$level = $parameters['level'];
				if (($operator === 'isNull' || (is_numeric($level) && in_array($operator, ['lte', 'gte']))))
				{
					/** @var $documentQuery \Change\Documents\Query\Query */
					$documentQuery = $event->getParam('documentQuery');
					$fragmentBuilder = $documentQuery->getFragmentBuilder();

					$hasSKU = $fragmentBuilder->neq($documentQuery->getColumn('sku'), $fragmentBuilder->number(0));
					$restriction = $fragmentBuilder->logicAnd($hasSKU, $this->buildPredicate($documentQuery, $fragmentBuilder, $operator, $level));
					$event->setParam('restriction', $restriction);
					$event->stopPropagation();
				}
			}
			elseif ($restriction === 'productCodes')
			{
				$parameters = $parameters + ['codeName' => null, 'operator' => null, 'value' => null];
				$codeName = $parameters['codeName'];
				$operator = $parameters['operator'];
				$value = $parameters['value'];

				if ($codeName && in_array($codeName, ['code', 'ean13', 'upc', 'jan', 'isbn', 'partNumber'])
					&& ($operator === 'isNull' || (is_string($value) && in_array($operator, ['eq', 'neq', 'contains', 'beginsWith', 'endsWith']))))
				{
					/** @var $documentQuery \Change\Documents\Query\Query */
					$documentQuery = $event->getParam('documentQuery');
					$fragmentBuilder = $documentQuery->getFragmentBuilder();

					$hasSKU = $fragmentBuilder->neq($documentQuery->getColumn('sku'), $documentQuery->getValueAsParameter(0, Property::TYPE_INTEGER));
					$codeRestriction = $this->buildCodePredicate($documentQuery, $fragmentBuilder, $codeName, $operator, $value);

					$restriction = $fragmentBuilder->logicAnd($hasSKU, $codeRestriction);
					$event->setParam('restriction', $restriction);
					$event->stopPropagation();
				}
			}
			elseif ($restriction === 'attributeValue')
			{
				$parameters = $parameters + ['attributeId' => null, 'operator' => null, 'value' => null];
				$attributeId = $parameters['attributeId'];
				$attribute = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($attributeId);
				$operator = $parameters['operator'];
				$value = $parameters['value'];

				if (($attribute instanceof Attribute) && ($operator === 'isNull' || (isset($value) && is_string($operator))))
				{
					/** @var $documentQuery \Change\Documents\Query\Query */
					$documentQuery = $event->getParam('documentQuery');
					$fragmentBuilder = $documentQuery->getFragmentBuilder();

					$hasAttribute = $fragmentBuilder->neq($documentQuery->getColumn('attribute'), $documentQuery->getValueAsParameter(0, Property::TYPE_INTEGER));
					$attributeRestriction = $this->buildAttributePredicate($documentQuery, $fragmentBuilder, $attribute, $operator, $value);
					if ($attributeRestriction)
					{
						$restriction = $fragmentBuilder->logicAnd($hasAttribute, $attributeRestriction);
						$event->setParam('restriction', $restriction);
						$event->stopPropagation();
					}
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\Query\Query $documentQuery
	 * @param \Change\Db\Query\SQLFragmentBuilder $fragmentBuilder
	 * @param string $operator
	 * @param $level
	 * @return Query\Predicates\Exists
	 */
	protected function buildPredicate($documentQuery, $fragmentBuilder, $operator, $level)
	{
		$sq = new \Change\Db\Query\SelectQuery();
		$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());

		$inventoryEntryTable = $fragmentBuilder->getDocumentTable('Rbs_Stock_InventoryEntry');
		$sq->setFromClause(new \Change\Db\Query\Clauses\FromClause($inventoryEntryTable));

		$docEq = $fragmentBuilder->eq($documentQuery->getColumn('sku'), $fragmentBuilder->getDocumentColumn('sku', $inventoryEntryTable));
		if ($operator == 'lte')
		{
			$levelRestriction = $fragmentBuilder->lte($fragmentBuilder->getDocumentColumn('level', $inventoryEntryTable), $fragmentBuilder->number($level));
			$where = new \Change\Db\Query\Clauses\WhereClause($fragmentBuilder->logicAnd($docEq, $levelRestriction));
			$sq->setWhereClause($where);
			return $fragmentBuilder->exists($sq);
		}
		elseif ($operator == 'gte')
		{
			$levelRestriction = $fragmentBuilder->gte($fragmentBuilder->getDocumentColumn('level', $inventoryEntryTable), $fragmentBuilder->number($level));
			$where = new \Change\Db\Query\Clauses\WhereClause($fragmentBuilder->logicAnd($docEq, $levelRestriction));
			$sq->setWhereClause($where);
			return $fragmentBuilder->exists($sq);
		}
		else
		{
			$where = new \Change\Db\Query\Clauses\WhereClause($docEq);
			$sq->setWhereClause($where);
			return $fragmentBuilder->notExists($sq);
		}
	}

	/**
	 * @param \Change\Documents\Query\Query $documentQuery
	 * @param \Change\Db\Query\SQLFragmentBuilder $fragmentBuilder
	 * @param string $codeName
	 * @param string $operator
	 * @param string|null $value
	 * @return Query\Predicates\Exists
	 */
	protected function buildCodePredicate($documentQuery, $fragmentBuilder, $codeName, $operator, $value)
	{
		$sq = new \Change\Db\Query\SelectQuery();
		$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());

		$skuTable = $fragmentBuilder->getDocumentTable('Rbs_Stock_Sku');
		$sq->setFromClause(new \Change\Db\Query\Clauses\FromClause($skuTable));

		$docEq = $fragmentBuilder->eq($documentQuery->getColumn('sku'), $fragmentBuilder->getDocumentColumn('id', $skuTable));
		$codeColumn = $fragmentBuilder->getDocumentColumn($codeName, $skuTable);

		switch ($operator)
		{
			case 'eq':
				$codeRestriction = $fragmentBuilder->eq($codeColumn, $documentQuery->getValueAsParameter($value, Property::TYPE_STRING));
				break;
			case 'neq':
				$codeRestriction = $fragmentBuilder->neq($codeColumn, $documentQuery->getValueAsParameter($value, Property::TYPE_STRING));
				break;
			case 'contains':
				$codeRestriction = $fragmentBuilder->like($codeColumn, $documentQuery->getValueAsParameter($value, Property::TYPE_STRING),
					\Change\Db\Query\Predicates\Like::ANYWHERE);
				break;
			case 'beginsWith':
				$codeRestriction = $fragmentBuilder->like($codeColumn, $documentQuery->getValueAsParameter($value, Property::TYPE_STRING),
					\Change\Db\Query\Predicates\Like::BEGIN);
				break;
			case 'endsWith':
				$codeRestriction = $fragmentBuilder->like($codeColumn, $documentQuery->getValueAsParameter($value, Property::TYPE_STRING),
					\Change\Db\Query\Predicates\Like::END);
				break;
			default:
				$codeRestriction = $fragmentBuilder->isNull($codeColumn);
				break;
		}
		$where = new \Change\Db\Query\Clauses\WhereClause($fragmentBuilder->logicAnd($docEq, $codeRestriction));
		$sq->setWhereClause($where);
		return $fragmentBuilder->exists($sq);
	}

	/**
	 * @param \Change\Documents\Query\Query $documentQuery
	 * @param \Change\Db\Query\SQLFragmentBuilder $fragmentBuilder
	 * @param Attribute $attribute
	 * @param string $operator
	 * @param string|null $value
	 * @return Query\Predicates\Exists|null
	 */
	protected function buildAttributePredicate($documentQuery, $fragmentBuilder, $attribute, $operator, $value)
	{
		$valueType = $attribute->getValueType();
		$attributeTable = $fragmentBuilder->table('rbs_catalog_dat_attribute');

		switch ($valueType)
		{
			case Attribute::TYPE_BOOLEAN:
				$valueColumn = $fragmentBuilder->column('integer_value', $attributeTable);
				$paramType = Property::TYPE_BOOLEAN;
				break;
			case Attribute::TYPE_INTEGER:
				$valueColumn = $fragmentBuilder->column('integer_value', $attributeTable);
				$paramType = Property::TYPE_INTEGER;
				break;
			case Attribute::TYPE_FLOAT:
				$valueColumn = $fragmentBuilder->column('float_value', $attributeTable);
				$paramType = Property::TYPE_FLOAT;
				break;
			case Attribute::TYPE_DATETIME:
				$valueColumn = $fragmentBuilder->column('date_value', $attributeTable);
				$paramType = Property::TYPE_DATETIME;
				break;
			case Attribute::TYPE_STRING:
				$valueColumn = $fragmentBuilder->column('string_value', $attributeTable);
				$paramType = Property::TYPE_STRING;
				break;
			default:
				return null;
		}

		switch ($operator)
		{
			case 'eq':
				$valueRestriction = $fragmentBuilder->eq($valueColumn, $documentQuery->getValueAsParameter($value, $paramType));
				break;
			case 'neq':
				$valueRestriction = $fragmentBuilder->neq($valueColumn, $documentQuery->getValueAsParameter($value, $paramType));
				break;
			case 'contains':
				$valueRestriction = $fragmentBuilder->like($valueColumn, $documentQuery->getValueAsParameter($value, $paramType),
					\Change\Db\Query\Predicates\Like::ANYWHERE);
				break;
			case 'beginsWith':
				$valueRestriction = $fragmentBuilder->like($valueColumn, $documentQuery->getValueAsParameter($value, $paramType),
					\Change\Db\Query\Predicates\Like::BEGIN);
				break;
			case 'endsWith':
				$valueRestriction = $fragmentBuilder->like($valueColumn, $documentQuery->getValueAsParameter($value, $paramType),
					\Change\Db\Query\Predicates\Like::END);
				break;
			case 'gte':
				$valueRestriction = $fragmentBuilder->gte($valueColumn, $documentQuery->getValueAsParameter($value, $paramType));
				break;
			case 'lte':
				$valueRestriction = $fragmentBuilder->lte($valueColumn, $documentQuery->getValueAsParameter($value, $paramType));
				break;
			case 'isNull':
				$valueRestriction = $fragmentBuilder->isNull($valueColumn);
				break;
			default:
				return null;
		}

		$sq = new \Change\Db\Query\SelectQuery();
		$sq->setSelectClause(new \Change\Db\Query\Clauses\SelectClause());
		$sq->setFromClause(new \Change\Db\Query\Clauses\FromClause($attributeTable));

		$docEq = $fragmentBuilder->eq($documentQuery->getColumn('id'), $fragmentBuilder->column('product_id', $attributeTable));
		$attrEq = $fragmentBuilder->eq($fragmentBuilder->column('attribute_id', $attributeTable), $documentQuery->getValueAsParameter($attribute->getId(), Property::TYPE_INTEGER));
		$where = new \Change\Db\Query\Clauses\WhereClause($fragmentBuilder->logicAnd($docEq, $attrEq, $valueRestriction));
		$sq->setWhereClause($where);
		return $fragmentBuilder->exists($sq);
	}
} 
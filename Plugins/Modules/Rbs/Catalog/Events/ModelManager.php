<?php
namespace Rbs\Catalog\Events;

use Change\Db\Query;

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
				'directiveClass' => 'rbs-document-filter-stock-inventory',
				'parameters' => ['restriction' => 'stockInventory'],
				'config' => [
					'listLabel' => $i18nManager->trans('m.rbs.catalog.admin.find_stock_inventory', $f),
					'group' => $i18nManager->trans('m.rbs.admin.admin.common_filter_group', $f)]];
			$filtersDefinition[] = $definition;
			$event->setParam('filtersDefinition', $filtersDefinition);
		}
	}

	public function getRestriction(\Change\Events\Event $event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']) && is_array($filter['parameters']))
		{
			$parameters = $filter['parameters'] + ['restriction' => null, 'operator' => null, 'level' => null];
			$restriction = $parameters['restriction'];
			$operator = $parameters['operator'];
			$level = $parameters['level'];
			if ($restriction === 'stockInventory' && ($operator === 'isNull' || (is_numeric($level) && in_array($operator, ['lte', 'gte']))))
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
} 
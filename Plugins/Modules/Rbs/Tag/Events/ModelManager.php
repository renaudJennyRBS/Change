<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Tag\Events;

/**
* @name \Rbs\Tag\Events\ModelManager
*/
class ModelManager
{
	public function getFiltersDefinition(\Change\Events\Event $event)
	{

		$model = $event->getParam('model');
		$filtersDefinition = $event->getParam('filtersDefinition');
		if ($model instanceof \Change\Documents\AbstractModel && is_array($filtersDefinition))
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$f = ['ucf'];
			$definition = ['name' => 'hasTag', 'directiveName' => 'rbs-document-filter-tags',
				'parameters' => ['restriction' => 'hasTag'],
				'config' => [
					'label' => $i18nManager->trans('m.rbs.tag.admin.tags', $f),
					'listLabel' => $i18nManager->trans('m.rbs.tag.admin.find', $f),
					'group' => $i18nManager->trans('m.rbs.admin.admin.common_filter_group', $f)]];
			$filtersDefinition[] = $definition;
			$event->setParam('filtersDefinition', $filtersDefinition);
		}
	}

	public function getRestriction(\Change\Events\Event $event)
	{
		$filter = $event->getParam('filter');
		if (isset($filter['parameters']['restriction']) && $filter['parameters']['restriction'] == 'hasTag'
			&& isset($filter['parameters']['tagIds']) )
		{
			$tagsId = $filter['parameters']['tagIds'];
			if (is_array($tagsId) && count($tagsId))
			{
				/** @var $documentQuery \Change\Documents\Query\Query */
				$documentQuery = $event->getParam('documentQuery');
				$fragmentBuilder = $documentQuery->getFragmentBuilder();

				/** @var $predicateBuilder \Change\Documents\Query\PredicateBuilder */
				$predicateBuilder = $event->getParam('predicateBuilder');

				$tags = [];
				foreach ($tagsId as $tagId)
				{
					$tags[] = $fragmentBuilder->number($tagId);
				}
				if (count($tags) == 1)
				{
					$restriction = new  \Rbs\Tag\Db\Query\HasTag();
					$restriction->setDocumentIdColumn($predicateBuilder->columnProperty('id'));
					$restriction->setTagId($tags[0]);
				}
				else
				{
					$restriction = new \Change\Db\Query\Predicates\Conjunction();
					foreach ($tags as $exp)
					{
						$hasTag = new  \Rbs\Tag\Db\Query\HasTag();
						$hasTag->setDocumentIdColumn($predicateBuilder->columnProperty('id'));
						$hasTag->setTagId($exp);
						$restriction->addArgument($hasTag);
					}
				}
				$event->setParam('restriction', $restriction);
				$event->stopPropagation();
			}
		}
	}
} 
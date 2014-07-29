<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Event\Blocks\Base;

/**
 * @name \Rbs\Event\Blocks\Base\BaseEventList
 */
abstract class BaseEventList extends \Change\Presentation\Blocks\Standard\Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('sectionId');
		$parameters->addParameterMeta('websiteId');
		$parameters->addParameterMeta('showTime', true);
		$parameters->addParameterMeta('showCategories', true);
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('contextualCategoryUrls', false);
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('pageNumber', 1);

		$request = $event->getHttpRequest();
		$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));
		$parameters->setLayoutParameters($event->getBlockLayout());

		/* @var $page \Rbs\Website\Documents\Page */
		$page = $event->getParam('page');
		$section = $page->getSection();
		if ($section instanceof \Rbs\Website\Documents\Section)
		{
			$parameters->setParameterValue('websiteId', $section->getWebsite()->getId());
			if ($parameters->getParameter('sectionId') === null)
			{
				$parameters->setParameterValue('sectionId', $section->getId());
			}
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$section = $documentManager->getDocumentInstance($parameters->getParameter('sectionId'));
		if (!($section instanceof \Rbs\Website\Documents\Section))
		{
			return null;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$website = $documentManager->getDocumentInstance($parameters->getParameter('websiteId'));
		if (!($website instanceof \Rbs\Website\Documents\Website))
		{
			return null;
		}

		return $this->doExecute($event, $attributes, $website, $section);
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @param \Rbs\Website\Documents\Website $website
	 * @param \Rbs\Website\Documents\Section $section
	 * @return string|null
	 */
	protected abstract function doExecute($event, $attributes, $website, $section);

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @param \Change\Documents\Query\Query $query
	 * @param \Rbs\Website\Documents\Website $website
	 * @param \Rbs\Website\Documents\Section $section
	 * @return boolean
	 */
	protected function renderList($event, $attributes, $query, $section, $website)
	{
		$totalCount = $query->getCountDocuments();
		if (!$totalCount)
		{
			return false;
		}

		$parameters = $event->getBlockParameters();
		$itemsPerPage = $parameters->getParameter('itemsPerPage');
		$pageCount = ceil($totalCount / $itemsPerPage);
		$pageNumber = $this->fixPageNumber($parameters->getParameter('pageNumber'), $pageCount);

		$attributes['pageNumber'] = $pageNumber;
		$attributes['totalCount'] = $totalCount;
		$attributes['pageCount'] = $pageCount;
		$attributes['section'] = $section;
		$attributes['items'] = array();

		$showCategories = $parameters->getParameter('showCategories');
		$contextualUrls = $parameters->getParameter('contextualUrls');
		$contextualCategoryUrls = $parameters->getParameter('contextualCategoryUrls');
		$urlManager = $event->getUrlManager();

		/* @var $document \Rbs\Event\Documents\BaseEvent */
		foreach ($query->getDocuments(($pageNumber-1)*$itemsPerPage, $itemsPerPage) as $document)
		{
			if ($contextualUrls)
			{
				$url = $urlManager->getByDocument($document, $section)->normalize()->toString();
			}
			else
			{
				$url = $urlManager->getCanonicalByDocument($document)->normalize()->toString();
			}
			$item = array('url' => $url, 'doc' => $document);
			$item['type'] = ($document instanceof \Rbs\Event\Documents\Event) ? 'event' : 'news';

			if ($showCategories)
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Event_Category');
				$query->andPredicates($query->published());
				$subQuery1 = $query->getModelBuilder('Rbs_Event_BaseEvent', 'categories');
				$subQuery1->andPredicates($subQuery1->eq('id', $document->getId()));
				$subQuery2 = $query->getPropertyBuilder('publicationSections');
				$treePredicateBuilder = new \Change\Documents\Query\TreePredicateBuilder($subQuery2, $event->getApplicationServices()->getTreeManager());
				$subQuery2->andPredicates(
					$subQuery2->getPredicateBuilder()->logicOr(
						$subQuery2->eq('id', $website->getId()),
						$treePredicateBuilder->descendantOf($website)
					)
				);
				$query->addOrder('title', true);

				/* @var $category \Rbs\Event\Documents\Category */
				$categoriesInfos = array();
				foreach ($query->getDocuments() as $category)
				{
					if ($contextualCategoryUrls)
					{
						$url = $urlManager->getByDocument($category, $section)->normalize()->toString();
					}
					else
					{
						$url = $urlManager->getCanonicalByDocument($category)->normalize()->toString();
					}
					$categoriesInfos[] = array('url' => $url, 'doc' => $category);
				}
				$item['categories'] = $categoriesInfos;
			}
			$attributes['items'][] = $item;
		}
		return true;
	}
}
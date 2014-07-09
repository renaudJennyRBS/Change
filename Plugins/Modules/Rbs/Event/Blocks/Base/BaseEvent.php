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
 * @name \Rbs\Event\Blocks\Base\BaseEvent
 */
abstract class BaseEvent extends \Change\Presentation\Blocks\Standard\Block
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
		/* @var $page \Rbs\Website\Documents\Page */
		$page = $event->getParam('page');
		$section = $page->getSection();
		$website = $section->getWebsite();

		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('showTime', true);
		$parameters->addParameterMeta('showCategories', true);
		$parameters->addParameterMeta('contextualUrls', true);
		$parameters->addParameterMeta('templateName');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters = $this->setParameterValueForDetailBlock($parameters, $event);

		$parameters->addParameterMeta('websiteId', $website->getId());
		if ($parameters->getParameter('contextualUrls'))
		{
			$parameters->addParameterMeta('sectionId', $section->getId());
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
		$docId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($docId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$document = $documentManager->getDocumentInstance($docId);
			if ($this->isValidDocument($document))
			{
				/* @var $document \Rbs\Event\Documents\BaseEvent */
				$attributes['doc'] = $document;
				$attributes['canonicalUrl'] = $event->getUrlManager()->getCanonicalByDocument($document)->normalize()->toString();

				if ($parameters->getParameter('showCategories'))
				{
					$website = $documentManager->getDocumentInstance($parameters->getParameter('websiteId'));
					$query = $documentManager->getNewQuery('Rbs_Event_Category');
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

					if ($parameters->getParameter('contextualUrls'))
					{
						$section = $documentManager->getDocumentInstance($parameters->getParameter('sectionId'));
					}
					else
					{
						$section = null;
					}

					/* @var $category \Rbs\Event\Documents\Category */
					$attributes['categories'] = array();
					foreach ($query->getDocuments() as $category)
					{
						if ($section instanceof \Change\Presentation\Interfaces\Section)
						{
							$url = $event->getUrlManager()->getByDocument($category, $section)->normalize()->toString();
						}
						else
						{
							$url = $event->getUrlManager()->getCanonicalByDocument($category)->normalize()->toString();
						}
						$attributes['categories'][] = array('url' => $url, 'doc' => $category);
					}
				}
				return $parameters->getParameter('templateName');
			}
		}
		return null;
	}
}
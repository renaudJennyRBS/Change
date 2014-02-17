<?php
namespace Rbs\Website\Events;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Web\PathRule;
use Change\Presentation\Interfaces\Section;

/**
 * @name \Rbs\Website\Events\PageResolver
 */
class PageResolver
{
	/**
	 * @param Event $event
	 */
	public function resolve($event)
	{
		$document = $event->getDocument();
		$pathRule = $event->getParam('pathRule');
		if ($pathRule instanceof PathRule && $document instanceof AbstractDocument)
		{
			$section = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
			if (!($section instanceof Section))
			{
				if ($document instanceof Publishable)
				{
					$section = $document->getCanonicalSection($event->getParam('website'));
				}

				if (!($section instanceof Section))
				{
					$section = $event->getParam('website');
				}
			}

			if ($section instanceof \Rbs\Website\Documents\Section)
			{
				$sectionPageFunction = $document->getDocumentModelName();
				$qp = $pathRule->getQueryParameters();
				if (isset($qp['sectionPageFunction']))
				{
					$sectionPageFunction = $qp['sectionPageFunction'];
				}

				$em = $section->getEventManager();
				$args = array('functionCode' => $sectionPageFunction);
				$event1 = new \Change\Documents\Events\Event('getPageByFunction', $section, $args);
				$em->trigger($event1);
				$page = $event1->getParam('page');
				if ($page instanceof \Rbs\Website\Documents\FunctionalPage)
				{
					$page->setSection($section);
				}
				$event->setParam('page', $page);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onPopulatePathRule(\Change\Events\Event $event)
	{
		$pathRule = $event->getParam('pathRule');
		$document = $event->getParam('document');
		if (($pathRule instanceof \Change\Http\Web\PathRule) && ($document instanceof Publishable))
		{
			$staticPage = null;
			if ($document instanceof \Rbs\Website\Documents\StaticPage)
			{
				$staticPage = $document;
			}
			elseif (!($document instanceof \Rbs\Website\Documents\Section))
			{
				$treeManager = $event->getApplicationServices()->getTreeManager();
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Website_StaticPage');
				$treePredicateBuilder = new \Change\Documents\Query\TreePredicateBuilder($query, $event->getApplicationServices()->getTreeManager());
				$web = $treePredicateBuilder->descendantOf($treeManager->getNodeById($pathRule->getWebsiteId()));
				$query->andPredicates($web, $query->eq('displayDocument', $document));
				$staticPage = $query->getFirstDocument();
			}

			if ($staticPage instanceof \Rbs\Website\Documents\StaticPage)
			{
				$documentAlias = $staticPage->getDisplayDocument();
				$pathRule->setDocumentAliasId($documentAlias ? $documentAlias->getId() : 0);
				$pathRule->setSectionId(0);

				$pathRule->setDocumentId($staticPage->getId());
				if ($staticPage !== $document)
				{
					$pathRule = $event->getApplicationServices()->getPathRuleManager()->populatePathRuleByDocument($pathRule, $staticPage);
					$event->setParam('pathRule', $pathRule);
					$event->stopPropagation();
				}
			}
		}
	}
}
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
			$section = $document->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
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

			if ($section instanceof Section)
			{
				$sectionPageFunction = $document->getDocumentModelName();
				$qp = $pathRule->getQueryParameters();
				if (isset($qp['sectionPageFunction']))
				{
					$sectionPageFunction = $qp['sectionPageFunction'];
				}

				$query = new \Change\Documents\Query\Query($document->getDocumentServices(), 'Rbs_Website_Page');
				$subQuery = $query->getModelBuilder('Rbs_Website_SectionPageFunction', 'page');
				$subQuery->andPredicates($subQuery->eq('section', $section), $subQuery->eq('functionCode', $sectionPageFunction));
				$page = $query->getFirstDocument();
				if ($page instanceof \Rbs\Website\Documents\FunctionalPage)
				{
					$page->setSection($section);
				}
				$event->setParam('page', $page);
			}
		}
	}
}
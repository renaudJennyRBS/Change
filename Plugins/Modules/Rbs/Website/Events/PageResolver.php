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
}
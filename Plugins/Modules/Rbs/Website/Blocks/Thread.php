<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Website\Blocks\Thread
 */
class Thread extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('templateName', 'thread.twig');
		$parameters->addParameterMeta('separator', '/');
		$parameters->addParameterMeta('pageId');
		$parameters->addParameterMeta('sectionId');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
			$parameters->setParameterValue('sectionId', $page->getSection()->getId());
			$parameters->setParameterValue('websiteId', $page->getSection()->getWebsite()->getId());
		}
		return $parameters;
	}

	/**
	 * @api
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout(), getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices(), getUrlManager()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		/* @var $urlManager \Change\Http\Web\UrlManager */
		$urlManager = $event->getUrlManager();
		$dm = $event->getDocumentServices()->getDocumentManager();
		$parameters = $event->getBlockParameters();
		$lastSection = null;

		$thread = array();
		$currentSection = $dm->getDocumentInstance($parameters->getSectionId());
		if ($currentSection instanceof \Rbs\Website\Documents\Section)
		{
			$website = $currentSection->getWebsite();
			$attributes['website'] = $website;
			foreach ($currentSection->getSectionThread() as $section)
			{
				$lastSection = $section;
				if ($section instanceof \Rbs\Website\Documents\Website)
				{
					continue;
				}
				$entry = new \Rbs\Website\Menu\MenuEntry();
				$entry->setTitle($section->getTitle());
				if ($section->getIndexPageId())
				{
					$entry->setUrl($urlManager->getCanonicalByDocument($section, $website));
				}
				$entry->setInPath(true);
				$thread[] = $entry;
			}
		}

		$page = $dm->getDocumentInstance($parameters->getPageId());
		if ($page instanceof \Rbs\Website\Documents\Page && $lastSection && $lastSection->getIndexPageId() !== $page->getId())
		{
			$entry = new \Rbs\Website\Menu\MenuEntry();
			$entry->setTitle($page->getTitle());
			$entry->setInPath(true);
			$entry->setCurrent(true);
			$thread[] = $entry;
		}

		$attributes['thread'] = $thread;

		return $parameters->getTemplateName();
	}
}
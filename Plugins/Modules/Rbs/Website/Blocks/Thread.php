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
		$parameters->addParameterMeta('templateName', Property::TYPE_STRING, true, 'thread.twig');
		$parameters->addParameterMeta('separator', Property::TYPE_STRING, true, '/');
		$parameters->addParameterMeta('pageId', Property::TYPE_INTEGER, false, null);
		$parameters->addParameterMeta('sectionId', Property::TYPE_INTEGER, false, null);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}
		$pathRule = $event->getParam('pathRule');
		if ($pathRule instanceof \Change\Http\Web\PathRule)
		{
			$parameters->setParameterValue('sectionId', $pathRule->getSectionId());
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
		$urlManager = $event->getUrlManager();
		$dm = $event->getDocumentServices()->getDocumentManager();
		$parameters = $event->getBlockParameters();
		$lastSection = null;

		$thread = array();
		$currentSection = $dm->getDocumentInstance($parameters->getSectionId());
		if ($currentSection instanceof \Rbs\Website\Documents\Section)
		{
			foreach ($currentSection->getSectionThread() as $section)
			{
				$lastSection = $section;
				if ($section instanceof \Rbs\Website\Documents\Website)
				{
					continue;
				}
				$entry = new \Rbs\Website\Menu\MenuEntry();
				$entry->setLabel($section->getLabel());
				if ($section->getIndexPageId())
				{
					$entry->setUrl($urlManager->getDefaultByDocument($section));
				}
				$entry->setInPath(true);
				$thread[] = $entry;
			}
		}

		$page = $dm->getDocumentInstance($parameters->getPageId());
		if ($page && $lastSection && $lastSection->getIndexPageId() !== $page->getId())
		{
			$entry = new \Rbs\Website\Menu\MenuEntry();
			$entry->setLabel($page->getLabel());
			$entry->setInPath(true);
			$entry->setCurrent(true);
			$thread[] = $entry;
		}

		$attributes['thread'] = $thread;

		return $parameters->getTemplateName();
	}
}
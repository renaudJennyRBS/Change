<?php
namespace Rbs\Website\Blocks;

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
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('templateName', 'thread.twig');
		$parameters->addParameterMeta('separator', '/');
		$parameters->addParameterMeta('sectionId');
		$parameters->addParameterMeta('documentId');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('sectionId', $page->getSection()->getId());
		}

		$document = $event->getParam('document');
		if ($document instanceof \Change\Documents\AbstractDocument)
		{
			$parameters->setParameterValue('documentId', $document->getId());
		}
		elseif ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('documentId', $page->getId());
		}
		return $parameters;
	}

	/**
	 * @api
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		/* @var $urlManager \Change\Http\Web\UrlManager */
		$urlManager = $event->getUrlManager();
		$dm = $event->getApplicationServices()->getDocumentManager();
		$parameters = $event->getBlockParameters();
		$document = $dm->getDocumentInstance($parameters->getParameter('documentId'));
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
				if ($section instanceof \Rbs\Website\Documents\Website || $section == $document)
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

		if ($document instanceof \Rbs\Website\Documents\Website)
		{
			// Nothing to add.
		}
		elseif ($document instanceof \Rbs\Website\Documents\Page)
		{
			if ($lastSection && $lastSection->getIndexPageId() !== $document->getId())
			{
				$thread[] = $this->getCurrentMenuEntry($document->getTitle());
			}
		}
		elseif ($document instanceof \Change\Documents\AbstractDocument)
		{
			$title = $document->getDocumentModel()->getPropertyValue($document, 'title');
			if ($title)
			{
				$thread[] = $this->getCurrentMenuEntry($title);
			}
		}

		$attributes['thread'] = $thread;

		return $parameters->getTemplateName();
	}

	/**
	 * @param string $title
	 * @return \Rbs\Website\Menu\MenuEntry
	 */
	protected function getCurrentMenuEntry($title)
	{
		$entry = new \Rbs\Website\Menu\MenuEntry();
		$entry->setTitle($title);
		$entry->setInPath(true);
		$entry->setCurrent(true);
		return $entry;
	}
}
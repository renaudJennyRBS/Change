<?php
namespace Change\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * TODO Sample
 * @name \Change\Website\Blocks\Menu
 */
class Menu extends Block
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
		$parameters->addParameterMeta('templateName', Property::TYPE_STRING, true, 'menu.twig');
		$parameters->addParameterMeta('showTitle', Property::TYPE_BOOLEAN, true, false);
		$parameters->addParameterMeta('documentId', Property::TYPE_DOCUMENT);
		$parameters->addParameterMeta('maxLevel', Property::TYPE_INTEGER, true, 1);
		$parameters->addParameterMeta('pageId', Property::TYPE_INTEGER, false, null);
		$parameters->addParameterMeta('sectionId', Property::TYPE_INTEGER, false, null);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$page = $event->getParam('page');
		if ($page instanceof \Change\Website\Documents\Page)
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
		$dm = $event->getDocumentServices()->getDocumentManager();
		$parameters = $event->getBlockParameters();
		$doc = $dm->getDocumentInstance($parameters->getDocumentId());
		if ($doc !== null)
		{
			$page = $dm->getDocumentInstance($parameters->getPageId());
			$section = $dm->getDocumentInstance($parameters->getSectionId());
			if ($section)
			{
				$path = $section->getSectionThread();
			}
			else
			{
				$path = array();
			}
			$attributes['root'] = $this->getMenuEntry($doc, $parameters->getMaxLevel(), $page, $path, $event->getUrlManager());
		}
		return $parameters->getTemplateName();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $doc
	 * @param integer $maxLevel
	 * @param \Change\Website\Documents\Page|null $currentPage
	 * @param \Change\Website\Documents\Section[] $path
	 * @param \Change\Http\UrlManager $urlManager
	 * @return \Change\Website\Menu\MenuEntry
	 */
	protected function getMenuEntry($doc, $maxLevel, $currentPage, $path, $urlManager)
	{
		$entry = new \Change\Website\Menu\MenuEntry();
		$entry->setLabel($doc->getLabel());
		if ($doc instanceof \Change\Website\Documents\Section)
		{
			if ($doc->getIndexPageId())
			{
				$entry->setUrl($urlManager->getDefaultByDocument($doc));
			}
			if (count($path) && in_array($doc, $path))
			{
				$entry->setInPath(true);
			}
		}
		else
		{
			$entry->setUrl($urlManager->getDefaultByDocument($doc));
			if ($currentPage === $doc)
			{
				$entry->setCurrent(true);
				$entry->setInPath(true);
			}
		}

		if ($maxLevel >= 1)
		{
			if ($doc instanceof \Change\Website\Documents\Section)
			{
				$tn = $doc->getDocumentServices()->getTreeManager()->getNodeByDocument($doc);
				foreach ($tn->getChildren() as $child)
				{
					$entry->addChild($this->getMenuEntry($child->getDocument(), $maxLevel-1, $currentPage, $path, $urlManager));
				}
			}
			elseif ($doc instanceof \Change\Website\Documents\Menu)
			{
				foreach ($doc->getItems() as $item)
				{
					//TODO
					//$entry->addChild($this->getMenuEntry($child->getDocument(), $maxLevel-1, $page, $path, $urlManager));
				}
			}
		}
		return $entry;
	}
}
<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * TODO Sample
 * @name \Rbs\Website\Blocks\Menu
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
		$parameters->addParameterMeta('websiteId', Property::TYPE_INTEGER, false, null);


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
			$parameters->setParameterValue('websiteId', $pathRule->getWebsiteId());
		}
		elseif ($page instanceof \Change\Presentation\Interfaces\Page && $page->getSection())
		{
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
		$dm = $event->getDocumentServices()->getDocumentManager();
		$parameters = $event->getBlockParameters();
		$doc = $dm->getDocumentInstance($parameters->getDocumentId());
		if ($doc !== null)
		{
			$website = $dm->getDocumentInstance($parameters->getWebsiteId());
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
			$attributes['root'] = $this->getMenuEntry($website, $doc, $parameters->getMaxLevel(), $page, $path, $event->getUrlManager());
		}
		return $parameters->getTemplateName();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param \Change\Documents\AbstractDocument $doc
	 * @param integer $maxLevel
	 * @param null|\Rbs\Website\Documents\Page $currentPage
	 * @param \Rbs\Website\Documents\Section[] $path
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return \Rbs\Website\Menu\MenuEntry
	 */
	protected function getMenuEntry($website, $doc, $maxLevel, $currentPage, $path, $urlManager)
	{
		$entry = new \Rbs\Website\Menu\MenuEntry();
		if ($doc instanceof \Change\Documents\Interfaces\Publishable)
		{
			$entry->setLabel($doc->getTitle());
		}
		if ($doc instanceof \Rbs\Website\Documents\Section)
		{
			if ($doc->getIndexPageId())
			{
				$entry->setUrl($urlManager->getCanonicalByDocument($doc, $website));
			}
			if (count($path) && in_array($doc, $path))
			{
				$entry->setInPath(true);
			}
		}
		else
		{
			$entry->setUrl($urlManager->getCanonicalByDocument($doc, $website));
			if ($currentPage === $doc)
			{
				$entry->setCurrent(true);
				$entry->setInPath(true);
			}
		}

		if ($maxLevel >= 1)
		{
			if ($doc instanceof \Rbs\Website\Documents\Section)
			{
				$treeManager = $doc->getDocumentServices()->getTreeManager();
				$tn = $treeManager->getNodeByDocument($doc);
				if ($tn)
				{
					foreach ($tn->setTreeManager($treeManager)->getChildren() as $child)
					{
						$entry->addChild($this->getMenuEntry($website, $child->getDocument(), $maxLevel-1, $currentPage, $path, $urlManager));
					}
				}
			}
			elseif ($doc instanceof \Rbs\Website\Documents\Menu)
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
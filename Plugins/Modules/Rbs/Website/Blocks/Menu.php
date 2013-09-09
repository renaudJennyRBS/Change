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
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $urlManager;

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
		$parameters->addParameterMeta('templateName', 'menu-vertical.twig');
		$parameters->addParameterMeta('showTitle', false);
		$parameters->addParameterMeta('documentId');
		$parameters->addParameterMeta('maxLevel', 1);
		$parameters->addParameterMeta('pageId');
		$parameters->addParameterMeta('sectionId');
		$parameters->addParameterMeta('websiteId');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
			$parameters->setParameterValue('sectionId', $page->getSection()->getId());
			$parameters->setParameterValue('websiteId', $page->getSection()->getWebsite()->getId());
		}

		if ($parameters->getParameter('documentId') === null)
		{
			$parameters->setParameterValue('documentId', $parameters->getParameter('sectionId'));
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
			/* @var $website \Rbs\Website\Documents\Website */
			$website = $dm->getDocumentInstance($parameters->getWebsiteId());
			/* @var $page \Rbs\Website\Documents\Page */
			$page = $dm->getDocumentInstance($parameters->getPageId());
			/* @var $section \Rbs\Website\Documents\Section */
			$section = $dm->getDocumentInstance($parameters->getSectionId());
			if ($section)
			{
				$path = $section->getSectionThread();
			}
			else
			{
				$path = array();
			}
			$this->urlManager = $event->getUrlManager();
			$this->i18nManager = $event->getPresentationServices()->getApplicationServices()->getI18nManager();
			$attributes['root'] = $this->getMenuEntry($website, $doc, $parameters->getMaxLevel(), $page, $path);
			$attributes['uniqueId'] = uniqid();
		}
		return $parameters->getTemplateName();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param \Change\Documents\AbstractDocument $doc
	 * @param integer $maxLevel
	 * @param null|\Rbs\Website\Documents\Page $currentPage
	 * @param \Rbs\Website\Documents\Section[] $path
	 * @return \Rbs\Website\Menu\MenuEntry
	 */
	protected function getMenuEntry($website, $doc, $maxLevel, $currentPage, $path)
	{
		$entry = new \Rbs\Website\Menu\MenuEntry();
		if ($doc instanceof \Change\Documents\Interfaces\Publishable)
		{
			$entry->setLabel($doc->getDocumentModel()->getPropertyValue($doc, 'title'));
		}
		if ($doc instanceof \Rbs\Website\Documents\Section)
		{
			if ($doc->getIndexPageId())
			{
				$entry->setUrl($this->urlManager->getCanonicalByDocument($doc, $website));
			}
			if (count($path) && in_array($doc, $path))
			{
				$entry->setInPath(true);
			}
		}
		else
		{
			$entry->setUrl($this->urlManager->getCanonicalByDocument($doc, $website));
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
						$entry->addChild($this->getMenuEntry($website, $child->getDocument(), $maxLevel-1, $currentPage, $path));
					}
				}
			}
			elseif ($doc instanceof \Rbs\Website\Documents\Menu)
			{
				foreach ($doc->getItems() as $item)
				{
					if (isset($item['documentId']))
					{
						$subDoc = $doc->getDocumentServices()->getDocumentManager()->getDocumentInstance($item['documentId']);
						if ($subDoc)
						{
							$subEntry = $this->getMenuEntry($website, $subDoc, $maxLevel-1, $currentPage, $path);
							if (isset($item['titleKey']))
							{
								$subEntry->setLabel($this->i18nManager->trans($item['titleKey'], ['ucf']));
							}
							elseif (isset($item['title']))
							{
								$subEntry->setLabel($item['title']);
							}

							$entry->addChild($subEntry);
						}
					}
					elseif (isset($item['url']) && (isset($item['title']) || isset($item['titleKey'])))
					{
						$subEntry = new \Rbs\Website\Menu\MenuEntry();
						if (isset($item['titleKey']))
						{
							$subEntry->setLabel($this->i18nManager->trans($item['titleKey'], ['ucf']));
						}
						else
						{
							$subEntry->setLabel($item['title']);
						}
						$subEntry->setUrl($item['url']);
						$entry->addChild($subEntry);
					}
				}
			}
		}
		return $entry;
	}
}
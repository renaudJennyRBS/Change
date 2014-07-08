<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Menu;

/**
 * @name \Rbs\Website\Menu\MenuComposer
 */
class MenuComposer
{
	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Documents\TreeManager
	 */
	protected $treeManager;

	/**
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Documents\TreeManager $treeManager
	 */
	public function __construct($urlManager, $i18nManager, $documentManager, $treeManager)
	{
		$this->urlManager = $urlManager;
		$this->i18nManager = $i18nManager;
		$this->documentManager = $documentManager;
		$this->treeManager = $treeManager;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param \Change\Documents\AbstractDocument $doc
	 * @param integer $maxLevel
	 * @param null|\Rbs\Website\Documents\Page $currentPage
	 * @param \Rbs\Website\Documents\Section[] $path
	 * @return \Rbs\Website\Menu\MenuEntry|null
	 */
	public function getMenuEntry($website, $doc, $maxLevel, $currentPage, $path)
	{
		$entry = new \Rbs\Website\Menu\MenuEntry();
		$entry->setTitle($doc->getDocumentModel()->getPropertyValue($doc, 'title'));

		if (!$doc instanceof \Rbs\Website\Documents\Menu)
		{
			if ($doc instanceof \Rbs\Website\Documents\Section)
			{
				$indexPage = $doc->getIndexPage();
				if (($indexPage instanceof \Change\Documents\Interfaces\Publishable && $indexPage->published())
					|| $indexPage instanceof \Rbs\Website\Documents\FunctionalPage)
				{
					$entry->setUrl($this->urlManager->getCanonicalByDocument($doc));
				}
				elseif ($maxLevel < 1)
				{
					return null; // Hide empty topics.
				}
				if (count($path) && in_array($doc, $path))
				{
					$entry->setInPath(true);
				}
			}
			else
			{
				$entry->setUrl($this->urlManager->getCanonicalByDocument($doc));
				if ($currentPage === $doc)
				{
					$entry->setCurrent(true);
					$entry->setInPath(true);
				}
			}
		}

		if ($maxLevel >= 1)
		{
			if ($doc instanceof \Rbs\Website\Documents\Section)
			{
				$tn = $this->treeManager->getNodeByDocument($doc);
				if ($tn)
				{
					foreach ($tn->setTreeManager($this->treeManager)->getChildren() as $child)
					{
						$childDoc = $child->getDocument();
						if ($this->shouldBeDisplayed($childDoc, $doc))
						{
							$entry->addChild($this->getMenuEntry($website, $childDoc, $maxLevel - 1, $currentPage,
								$path));
						}
					}
					if (!$entry->getUrl() && !count($entry->getChildren()))
					{
						return null; // Hide empty topics.
					}
				}
			}
			elseif ($doc instanceof \Rbs\Website\Documents\Menu)
			{
				$items = $doc->getCurrentLocalization()->getItems();
				if (is_array($items))
				{
					foreach ($items as $item)
					{
						if (isset($item['documentId']))
						{
							$childDoc = $this->documentManager->getDocumentInstance($item['documentId']);
							if ($this->shouldBeDisplayed($childDoc, $doc))
							{
								$subEntry = $this->getMenuEntry($website, $childDoc, $maxLevel - 1, $currentPage, $path);
								if ($subEntry !== null)
								{
									if (isset($item['titleKey']))
									{
										$subEntry->setTitle($this->i18nManager->trans($item['titleKey'], ['ucf']));
									}
									elseif (isset($item['title']))
									{
										$subEntry->setTitle($item['title']);
									}
									$entry->addChild($subEntry);
								}
							}
						}
						elseif (isset($item['url']) && (isset($item['title']) || isset($item['titleKey'])))
						{
							$subEntry = new \Rbs\Website\Menu\MenuEntry();
							if (isset($item['titleKey']))
							{
								$subEntry->setTitle($this->i18nManager->trans($item['titleKey'], ['ucf']));
							}
							else
							{
								$subEntry->setTitle($item['title']);
							}
							$subEntry->setUrl($item['url']);
							$entry->addChild($subEntry);
						}
					}
				}
			}
		}
		return $entry;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $doc
	 * @param \Change\Documents\AbstractDocument $parent
	 * @return boolean
	 */
	protected function shouldBeDisplayed($doc, $parent)
	{
		if (!($doc instanceof \Change\Documents\Interfaces\Publishable) || !$doc->published())
		{
			return false;
		}
		if (!($parent instanceof \Rbs\Website\Documents\Menu))
		{
			if ($doc instanceof \Rbs\Website\Documents\StaticPage && $doc->getHideLinks())
			{
				return false;
			}
		}
		return true;
	}
} 
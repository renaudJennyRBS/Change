<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Theme\Documents;

use Change\Presentation\Layout\Layout;
use Zend\Json\Json;

/**
 * @name \Rbs\Theme\Documents\Template
 */
class Template extends \Compilation\Rbs\Theme\Documents\Template implements \Change\Presentation\Interfaces\Template
{
	const FILE_META_KEY = "fileMeta";
	/**
	 * @param integer $websiteId
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout($websiteId = null)
	{
		$editableContent = $this->getEditableContent();
		if ($this->getApplication()->inDevelopmentMode())
		{
			$fileMetas = $this->getMeta(static::FILE_META_KEY);
			if (isset($fileMetas['json']) && file_exists($fileMetas['json']))
			{
				if (filemtime($fileMetas['json']) > $this->getModificationDate()->getTimestamp())
				{
					$jsonData = Json::decode(file_get_contents($fileMetas['json']), Json::TYPE_ARRAY);
					if (isset($jsonData['editableContent']) && is_array($jsonData['editableContent']))
					{
						$editableContent = array_merge($jsonData['editableContent'], $editableContent);
					}
				}
			}
		}
		if ($websiteId)
		{
			$contentByWebsite = $this->getContentByWebsite();
			if (is_array($contentByWebsite) && isset($contentByWebsite[$websiteId]) && is_array($contentByWebsite[$websiteId]))
			{
				$editableContent = array_merge($editableContent, $contentByWebsite[$websiteId]);
			}
		}
		return new Layout($editableContent);
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		if ($this->getApplication()->inDevelopmentMode())
		{
			$fileMetas = $this->getMeta(static::FILE_META_KEY);
			if (isset($fileMetas['html']) && file_exists($fileMetas['html']))
			{
				if (filemtime($fileMetas['html']) > $this->getModificationDate()->getTimestamp())
				{
					$this->application->getLogging()->warn('Please update template ' . $this->getName());
					return file_get_contents($fileMetas['html']);
				}
			}
		}
		return $this->getHtmlData();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getId();
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$documentLink = $restResult;
			/* @var $pageTemplate \Rbs\Theme\Documents\Template */
			$pageTemplate = $documentLink->getDocument();
			$theme = $pageTemplate->getTheme();
			if ($theme)
			{
				$documentLink->setProperty('label', $theme->getLabel() . ' > ' . $pageTemplate->getLabel());
				$documentLink->setProperty('themeId', $theme->getId());
			}
			$documentLink->setProperty('mailSuitable', $pageTemplate->getMailSuitable());
			$documentLink->setProperty('categoryName', $pageTemplate->getMailSuitable() ? 'MailTemplates' : 'PageTemplates');
			$restResult->removeRelAction('delete');
		}
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			/* @var $pageTemplate \Rbs\Theme\Documents\Template */
			$pageTemplate = $restResult->getDocument();
			$theme = $pageTemplate->getTheme();
			if ($theme)
			{
				$restResult->setProperty('themeId', $theme->getId());
			}
			$restResult->setProperty('categoryName', $pageTemplate->getMailSuitable() ? 'MailTemplates' : 'PageTemplates');
			$restResult->removeRelAction('delete');
		}
	}

	/**
	 * @return bool
	 */
	public function isMailSuitable()
	{
		return $this->getMailSuitable();
	}
}
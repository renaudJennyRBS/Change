<?php
namespace Rbs\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Rbs\Theme\Documents\PageTemplate
 */
class PageTemplate extends \Compilation\Rbs\Theme\Documents\PageTemplate implements \Change\Presentation\Interfaces\PageTemplate
{
	/**
	 * @param integer $websiteId
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout($websiteId = null)
	{
		$editableContent = $this->getEditableContent();
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
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$documentLink = $restResult;
			/* @var $pageTemplate \Rbs\Theme\Documents\PageTemplate */
			$pageTemplate = $documentLink->getDocument();
			$theme = $pageTemplate->getTheme();
			if ($theme)
			{
				$documentLink->setProperty('label', $theme->getLabel() . ' > ' . $pageTemplate->getLabel());
			}
		}
	}
}
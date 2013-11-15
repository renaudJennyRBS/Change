<?php
namespace Rbs\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Rbs\Theme\Documents\PageTemplate
 */
class PageTemplate extends \Compilation\Rbs\Theme\Documents\PageTemplate implements \Change\Presentation\Interfaces\PageTemplate
{
	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getEditableContent());
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
			$documentLink->setProperty('label', $pageTemplate->getTheme()->getLabel() . ' > ' . $pageTemplate->getLabel());
		}
	}
}
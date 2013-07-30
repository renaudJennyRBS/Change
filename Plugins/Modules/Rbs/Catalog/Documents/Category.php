<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\Category
 */
class Category extends \Compilation\Rbs\Catalog\Documents\Category
{
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getSection() ? $this->getSection()->getTitle() : null;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		// Do nothing.
		return $this;
	}

	/**
	 * @return array|\Change\Documents\AbstractDocument
	 */
	public function getPublicationSections()
	{
		if ($this->getSection())
		{
			return array($this->getSection());
		}
		return array();
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof DocumentResult)
			{
				$selfLinks = $result->getRelLink('self');
				$selfLink = array_shift($selfLinks);
				if ($selfLink instanceof Link)
				{
					$pathParts = explode('/', $selfLink->getPathInfo());
					array_pop($pathParts);
					$link = new Link($event->getParam('urlManager'), implode('/', $pathParts) . '/ProductCategorization/', 'productcategorizations');
					$result->addLink($link);
				}
			}
		}, 5);
	}
}
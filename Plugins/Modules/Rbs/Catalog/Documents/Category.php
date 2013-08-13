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
	 * @param DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		$selfLinks = $documentResult->getRelLink('self');
		$selfLink = array_shift($selfLinks);
		if ($selfLink instanceof Link)
		{
			$pathParts = explode('/', $selfLink->getPathInfo());
			array_pop($pathParts);
			$link = new Link($documentResult->getUrlManager(), implode('/', $pathParts) . '/ProductCategorization/', 'productcategorizations');
			$documentResult->addLink($link);
		}
	}
}
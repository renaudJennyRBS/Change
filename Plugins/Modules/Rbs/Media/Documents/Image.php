<?php
namespace Rbs\Media\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;
use Rbs\Media\Std\Resizer;

/**
 * @name \Rbs\Media\Documents\Image
 */
class Image extends \Compilation\Rbs\Media\Documents\Image
{
	/**
	 * @var array
	 */
	protected $imageSize = false;

	/**
	 * @return array
	 */
	public function getImageSize()
	{
		// Load the storage manager even if not used in the function itself
		$sm = $this->getApplicationServices()->getStorageManager();
		if ($this->imageSize === false)
		{
			$this->imageSize = (new Resizer())->getImageSize($this->getPath());
		}
		return $this->imageSize;
	}

	/**
	 *
	 */
	public function onUpdate()
	{
		$this->updateImageSizeProperties();
	}

	/**
	 *
	 */
	public function onCreate()
	{
		$this->updateImageSizeProperties();
	}

	/**
	 *
	 */
	protected function updateImageSizeProperties()
	{
		if ($this->isPropertyModified('path'))
		{
			$size = $this->getImageSize();
			$this->setHeight($size['height']);
			$this->setWidth($size['width']);
		}
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->getApplicationServices()->getStorageManager()->getMimeType($this->getPath());
	}

	/**
	 * @param string $mimeType
	 * @return $this
	 */
	public function setMimeType($mimeType)
	{
		// TODO: Implement setMimeType() method.
		return $this;
	}

	/**
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @return null|string
	 */
	public function getPublicURL($maxWidth = 0, $maxHeight = 0)
	{
		$sm = $this->getApplicationServices()->getStorageManager();
		$query = array();
		if (intval($maxWidth))
		{
			$query['max-width'] = intval($maxWidth);
		}
		if (intval($maxHeight))
		{
			$query['max-height'] = intval($maxHeight);
		}
		$changeUri = $this->getPath();
		if (count($query))
		{
			$changeUri .= '?' . http_build_query($query);
		}
		return $sm->getPublicURL($changeUri);
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);
		$pathParts = explode('/', $documentLink->getPathInfo());
		array_pop($pathParts);
		$documentLink->setProperty('actions', array(new Link($documentLink->getUrlManager(), implode('/', $pathParts) . '/resize', 'resizeurl')));
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		/* @var $document Image */
		$document = $documentResult->getDocument();
		$link = array('rel' => 'publicurl', 'href' => $document->getPublicURL());
		$documentResult->addLink($link);
		$selfLinks = $documentResult->getRelLink('self');
		$selfLink = array_shift($selfLinks);
		if ($selfLink instanceof Link)
		{
			$pathParts = explode('/', $selfLink->getPathInfo());
			array_pop($pathParts);
			$link = new Link($documentResult->getUrlManager(), implode('/', $pathParts) . '/resize', 'resizeurl');
			$documentResult->addAction($link);
		}
	}

}